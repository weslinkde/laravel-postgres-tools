<?php

namespace Weslinkde\PostgresTools\Support;

use Illuminate\Support\Arr;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Exceptions\DatabaseOperationFailed;
use Weslinkde\PostgresTools\Exceptions\RestoreFailed;
use Weslinkde\PostgresTools\Snapshot;
use Weslinkde\PostgresTools\SnapshotFactory;

class PostgresHelper
{
    /**
     * The magic string every pg_dump archive (custom, tar and directory format) starts with.
     */
    private const ARCHIVE_MAGIC = 'PGDMP';

    private string $connection;

    private string $name;

    private string $host;

    private int $port;

    private string $password;

    private string $userName;

    public static function createForConnection(string $connectionName): PostgresHelper
    {
        $dbConfig = config("database.connections.{$connectionName}");

        if (is_null($dbConfig)) {
            throw CannotCreateConnection::connectionDoesNotExist($connectionName);
        }

        if ($dbConfig['driver'] !== 'pgsql') {
            throw CannotCreateConnection::driverNotSupported($dbConfig['driver']);
        }

        $fallback = Arr::get(
            $dbConfig,
            'read.host',
            Arr::get($dbConfig, 'host')
        );

        $dbHost = Arr::get(
            $dbConfig,
            'read.host.0',
            $fallback
        );

        $dbName = $dbConfig['connect_via_database'] ?? $dbConfig['database'];

        $postgresHelper = (new self)
            ->setConnection($connectionName)
            ->setHost($dbHost ?? '')
            ->setName($dbName)
            ->setUserName($dbConfig['username'] ?? '')
            ->setPassword($dbConfig['password'] ?? '');

        if (isset($dbConfig['port'])) {
            return $postgresHelper->setPort($dbConfig['port']);
        }

        return $postgresHelper;
    }

    public function createSnapshot(string $snapshotName, ?array $tables = null, ?array $exclude = null, ?array $excludeTableData = null): Snapshot
    {
        $snapshotFactory = app(SnapshotFactory::class);

        return $snapshotFactory->create(
            $snapshotName,
            config('postgres-tools.disk'),
            $this->connection,
            false,
            $tables,
            $exclude,
            $excludeTableData,
            $this->name
        );
    }

    /**
     * Resolve a PostgreSQL client binary, honouring the configured `postgres-tools.bin_path`.
     */
    public function binary(string $name): string
    {
        $binPath = trim((string) config('postgres-tools.bin_path', ''));

        if ($binPath === '') {
            return $name;
        }

        return rtrim($binPath, '/').'/'.$name;
    }

    /**
     * @throws DatabaseOperationFailed
     */
    public function createDatabase(): Process|bool
    {
        // check if a database exists, if not create
        if ($this->checkIfDatabaseExists($this->name)) {
            return false;
        }

        $process = new Process(
            command: [$this->binary('createdb'), '--host', $this->host, '--port', (string) $this->port, '--username', $this->userName, '--owner', $this->userName, $this->name],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0); // 0 = no timeout

        $process->run();

        if (! $process->isSuccessful()) {
            throw DatabaseOperationFailed::couldNotCreateDatabase($this->name, $process);
        }

        return $process;
    }

    /**
     * @throws DatabaseOperationFailed
     */
    public function dropDatabase(): Process|bool
    {
        // check if a database exists, if not go out
        if (! $this->checkIfDatabaseExists($this->name)) {
            return false;
        }

        $process = new Process(
            command: [$this->binary('dropdb'), '--host', $this->host, '--port', (string) $this->port, '--username', $this->userName, '--force', $this->name],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0); // 0 = no timeout

        $process->run();

        if (! $process->isSuccessful()) {
            throw DatabaseOperationFailed::couldNotDropDatabase($this->name, $process);
        }

        return $process;
    }

    /**
     * @throws RestoreFailed
     */
    public function restoreSnapshot(string $filePath): Process
    {
        $jobs = (string) config('postgres-tools.jobs', 1);

        $process = new Process(
            command: [$this->binary('pg_restore'), '--jobs', $jobs, '--host', $this->host, '--port', (string) $this->port, '--username', $this->userName, '--no-owner', '--clean', '--if-exists', '--role', $this->userName, '--dbname', $this->name, $filePath],
            env: ['PGPASSWORD' => $this->password]
        );

        $process->setTimeout(0); // 0 = no timeout
        $process->run();

        if (! $process->isSuccessful()) {
            throw RestoreFailed::processDidNotEndSuccessfully($process);
        }

        return $process;
    }

    /**
     * Verify the local client can actually read the archive.
     *
     * `pg_restore --list` only reads the archive header and table of contents and never
     * connects to a database, so this can run before any table is dropped. Without it an
     * unreadable archive - a truncated file, or one written by a newer pg_dump than the
     * local client understands - is only discovered after the target database has been
     * emptied.
     *
     * @throws RestoreFailed
     */
    public function assertSnapshotIsRestorable(string $filePath): void
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw RestoreFailed::snapshotFileIsNotReadable($filePath);
        }

        $process = new Process(command: [$this->binary('pg_restore'), '--list', $filePath]);
        $process->setTimeout(0); // 0 = no timeout
        $process->run();

        if (! $process->isSuccessful()) {
            throw RestoreFailed::archiveCannotBeReadByClient(
                $filePath,
                self::readArchiveVersion($filePath),
                $this->clientVersion(),
                $process
            );
        }
    }

    /**
     * Read the archive format version out of a pg_dump archive header, e.g. `1.15`.
     *
     * Returns `null` when the file is not a pg_dump archive (a plain SQL dump, for example).
     */
    public static function readArchiveVersion(string $filePath): ?string
    {
        $handle = @fopen($filePath, 'rb');

        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 8);
        fclose($handle);

        if (! is_string($header) || strlen($header) < 8 || ! str_starts_with($header, self::ARCHIVE_MAGIC)) {
            return null;
        }

        return ord($header[5]).'.'.ord($header[6]);
    }

    /**
     * The version banner of a local client binary, e.g. `pg_restore (PostgreSQL) 16.15`.
     */
    public function clientVersion(string $binary = 'pg_restore'): string
    {
        $process = new Process(command: [$this->binary($binary), '--version']);
        $process->run();

        return $process->isSuccessful() ? trim($process->getOutput()) : 'unknown';
    }

    /**
     * @throws DatabaseOperationFailed
     */
    public function checkIfDatabaseExists(string $databaseName): bool
    {
        $process = new Process(
            command: [
                $this->binary('psql'),
                '--host', $this->host,
                '--port', (string) $this->port,
                '--username', $this->userName,
                '--dbname', 'postgres',
                '--no-psqlrc',
                '--tuples-only',
                '--no-align',
                '--set', 'ON_ERROR_STOP=1',
                '--set', "dbname={$databaseName}",
                // Read the statement from stdin so psql interpolates `:'dbname'` into a
                // properly quoted SQL literal - `--command` does no interpolation at all.
                '--file', '-',
            ],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0); // 0 = no timeout
        $process->setInput("SELECT 1 FROM pg_catalog.pg_database WHERE datname = :'dbname'\n");

        $process->run();

        // A failing query must not be reported as "the database does not exist".
        if (! $process->isSuccessful()) {
            throw DatabaseOperationFailed::queryFailed("check whether the database `{$databaseName}` exists", $process);
        }

        return trim($process->getOutput()) === '1';
    }

    /**
     * List all PostgreSQL databases with their owner and size.
     *
     * @return array<int, array{name: string, owner: string, size: int}>
     *
     * @throws DatabaseOperationFailed
     */
    public function listDatabases(): array
    {
        $sql = 'SELECT d.datname, r.rolname as owner, pg_database_size(d.datname) as size
                FROM pg_catalog.pg_database d
                JOIN pg_catalog.pg_roles r ON d.datdba = r.oid
                WHERE d.datistemplate = false
                ORDER BY d.datname';

        $process = new Process(
            command: [
                $this->binary('psql'),
                '--host', $this->host,
                '--port', (string) $this->port,
                '--username', $this->userName,
                '--dbname', 'postgres',
                '--no-psqlrc',
                '--tuples-only',
                '--no-align',
                '--set', 'ON_ERROR_STOP=1',
                '--field-separator', '|',
                '--command', $sql,
            ],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0);
        $process->run();

        if (! $process->isSuccessful()) {
            throw DatabaseOperationFailed::queryFailed('list the databases', $process);
        }

        $output = trim($process->getOutput());
        if ($output === '' || $output === '0') {
            return [];
        }

        return collect(explode("\n", $output))
            ->filter()
            ->map(function (string $line): array {
                $parts = explode('|', $line);

                return [
                    'name' => trim($parts[0]),
                    'owner' => trim($parts[1] ?? ''),
                    'size' => (int) trim($parts[2] ?? '0'),
                ];
            })
            ->toArray();
    }

    public function setConnection(string $connection): PostgresHelper
    {
        $this->connection = $connection;

        return $this;
    }

    public function setName(string $name): PostgresHelper
    {
        $this->name = $name;

        return $this;
    }

    public function setHost(string $host): PostgresHelper
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port): PostgresHelper
    {
        $this->port = $port;

        return $this;
    }

    public function setPassword(string $password): PostgresHelper
    {
        $this->password = $password;

        return $this;
    }

    public function setUserName(string $userName): PostgresHelper
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get database size information including total size and table sizes.
     *
     * @return array{database: string, total_size: int, tables: array<int, array{name: string, size: int, rows: int}>}
     *
     * @throws DatabaseOperationFailed
     */
    public function getDatabaseSize(): array
    {
        // Get total database size
        $totalSizeSql = 'SELECT pg_database_size(current_database()) as size';

        $process = new Process(
            command: [
                $this->binary('psql'),
                '--host', $this->host,
                '--port', (string) $this->port,
                '--username', $this->userName,
                '--dbname', $this->name,
                '--no-psqlrc',
                '--tuples-only',
                '--no-align',
                '--set', 'ON_ERROR_STOP=1',
                '--command', $totalSizeSql,
            ],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0);
        $process->run();

        if (! $process->isSuccessful()) {
            throw DatabaseOperationFailed::queryFailed("read the size of database `{$this->name}`", $process);
        }

        $totalSize = (int) trim($process->getOutput());

        // Get table sizes
        $tablesSql = "SELECT schemaname || '.' || relname as name,
                             pg_total_relation_size(schemaname || '.' || relname) as size,
                             n_live_tup as rows
                      FROM pg_stat_user_tables
                      ORDER BY pg_total_relation_size(schemaname || '.' || relname) DESC";

        $process = new Process(
            command: [
                $this->binary('psql'),
                '--host', $this->host,
                '--port', (string) $this->port,
                '--username', $this->userName,
                '--dbname', $this->name,
                '--no-psqlrc',
                '--tuples-only',
                '--no-align',
                '--set', 'ON_ERROR_STOP=1',
                '--field-separator', '|',
                '--command', $tablesSql,
            ],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0);
        $process->run();

        if (! $process->isSuccessful()) {
            throw DatabaseOperationFailed::queryFailed("read the table sizes of database `{$this->name}`", $process);
        }

        $output = trim($process->getOutput());
        $tables = [];

        if ($output !== '' && $output !== '0') {
            $tables = collect(explode("\n", $output))
                ->filter()
                ->map(function (string $line): array {
                    $parts = explode('|', $line);

                    return [
                        'name' => trim($parts[0]),
                        'size' => (int) trim($parts[1] ?? '0'),
                        'rows' => (int) trim($parts[2] ?? '0'),
                    ];
                })
                ->toArray();
        }

        return [
            'database' => $this->name,
            'total_size' => $totalSize,
            'tables' => $tables,
        ];
    }

    /**
     * Run VACUUM ANALYZE on the database or specific tables.
     *
     * @param  array<string>|null  $tables  Specific tables to vacuum, or null for all
     */
    public function vacuumAnalyze(?array $tables = null): Process
    {
        if ($tables === null || $tables === []) {
            $sql = 'VACUUM ANALYZE';
        } else {
            $tableList = implode(', ', array_map(fn ($t): string => '"'.str_replace('"', '""', $t).'"', $tables));
            $sql = "VACUUM ANALYZE {$tableList}";
        }

        $process = new Process(
            command: [
                $this->binary('psql'),
                '--host', $this->host,
                '--port', (string) $this->port,
                '--username', $this->userName,
                '--dbname', $this->name,
                '--no-psqlrc',
                '--set', 'ON_ERROR_STOP=1',
                '--command', $sql,
            ],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0);
        $process->run();

        return $process;
    }

    /**
     * Dump only the database schema (no data).
     */
    public function dumpSchema(string $outputFile): Process
    {
        $process = new Process(
            command: [
                $this->binary('pg_dump'),
                '--host', $this->host,
                '--port', (string) $this->port,
                '--username', $this->userName,
                '--dbname', $this->name,
                '--schema-only',
                '--no-owner',
                '--no-acl',
                '--file', $outputFile,
            ],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0);
        $process->run();

        return $process;
    }

    /**
     * Dump only the database data (no schema).
     *
     * @param  array<string>|null  $tables  Specific tables to dump, or null for all
     */
    public function dumpData(string $outputFile, ?array $tables = null): Process
    {
        $command = [
            $this->binary('pg_dump'),
            '--host', $this->host,
            '--port', (string) $this->port,
            '--username', $this->userName,
            '--dbname', $this->name,
            '--data-only',
            '--no-owner',
            '--no-acl',
            '--file', $outputFile,
        ];

        if ($tables !== null && $tables !== []) {
            foreach ($tables as $table) {
                $command[] = '--table';
                $command[] = $table;
            }
        }

        $process = new Process(
            command: $command,
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0);
        $process->run();

        return $process;
    }
}
