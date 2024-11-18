<?php

namespace Weslinkde\PostgresTools\Support;

use Illuminate\Support\Arr;
use Spatie\DbSnapshots\SnapshotFactory;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\DbDumperFactory;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;

class PostgresHelper
{
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

        $postgresHelper = (new self())
            ->setConnection($connectionName)
            ->setHost($dbHost ?? '')
            ->setName($dbName)
            ->setUserName($dbConfig['username'] ?? '')
            ->setPassword($dbConfig['password'] ?? '');

        if (isset($dbConfig['port'])) {
            $postgresHelper = $postgresHelper->setPort($dbConfig['port']);
        }

        return $postgresHelper;
    }

    public function createSnapshot(string $snapshotName, ?array $tables = null, ?array $exclude = null)
    {
        $snapshotFactory = app(SnapshotFactory::class, ['dumperFactory' => new DbDumperFactory()]);

        return $snapshotFactory->create(
            $snapshotName,
            config('postgres-tools.disk'),
            $this->connection,
            false,
            $tables,
            $exclude
        );
    }

    public function createDatabase(): Process|bool
    {
        // check if a database exists, if not create
        if ($this->checkIfDatabaseExists($this->name)) {
            return false;
        }

        $process = new Process(
            command: ['createdb', '--host', $this->host, '--port', $this->port, '--username', $this->userName, '--owner', $this->userName, $this->name],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0); // 0 = no timeout

        $process->run();

        return $process;
    }

    public function dropDatabase(): Process|bool
    {
        // check if a database exists, if not go out
        if (! $this->checkIfDatabaseExists($this->name)) {
            return false;
        }

        $process = new Process(
            command: ['dropdb', '--host', $this->host, '--port', $this->port, '--username', $this->userName, '--force', $this->name],
            env: ['PGPASSWORD' => $this->password]
        );
        $process->setTimeout(0); // 0 = no timeout

        $process->run();

        return $process;
    }

    public function restoreSnapshot(string $filePath): Process
    {
        $jobs = config('postgres-tools.jobs', 1);

        $process = new Process(
            command: ['pg_restore', '--jobs', $jobs ,'--host', $this->host, '--port', $this->port, '--username', $this->userName, '--no-owner', '--clean', '--if-exists', '--role', $this->userName, '--dbname', $this->name, $filePath],
            env: ['PGPASSWORD' => $this->password]
        );

        $process->setTimeout(0); // 0 = no timeout
        $process->run();

        return $process;
    }

    public function checkIfDatabaseExists(string $databaseName): bool
    {
        $sql = "SELECT datname FROM pg_catalog.pg_database WHERE datname = '$databaseName'";

        $process = new Process(
            command: ['psql', '--host', $this->host, '--port', $this->port, '--username', $this->userName, '--dbname', 'postgres', '--command', $sql],
            env: ['PGPASSWORD' => $this->password]
        );

        $process->run();
        if (str_contains($process->getOutput(), $databaseName)) {
            return true;
        }

        return false;
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
}
