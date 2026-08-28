<?php

namespace Weslinkde\PostgresTools\Dumper;

use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\Dumper\Exceptions\CannotSetParameter;
use Weslinkde\PostgresTools\Dumper\Exceptions\CannotStartDump;
use Weslinkde\PostgresTools\Dumper\Exceptions\DumpFailed;

class PostgresDumper
{
    protected string $dbName = '';

    protected string $userName = '';

    protected string $password = '';

    protected string $host = 'localhost';

    protected int $port = 5432;

    protected int $timeout = 0;

    protected string $dumpBinaryPath = '';

    protected array $includeTables = [];

    protected array $excludeTables = [];

    protected array $extraOptions = [];

    protected bool $useInserts = false;

    protected bool $createTables = true;

    protected bool $includeData = true;

    /** @var false|resource */
    private $tempFileHandle;

    public static function create(): self
    {
        return new self;
    }

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): self
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function setUserName(string $userName): self
    {
        $this->userName = $userName;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setDumpBinaryPath(string $dumpBinaryPath = ''): self
    {
        if ($dumpBinaryPath !== '' && ! str_ends_with($dumpBinaryPath, '/')) {
            $dumpBinaryPath .= '/';
        }

        $this->dumpBinaryPath = $dumpBinaryPath;

        return $this;
    }

    public function includeTables(string|array $includeTables): self
    {
        if ($this->excludeTables !== []) {
            throw CannotSetParameter::conflictingParameters('includeTables', 'excludeTables');
        }

        if (! is_array($includeTables)) {
            $includeTables = explode(', ', $includeTables);
        }

        $this->includeTables = $includeTables;

        return $this;
    }

    public function excludeTables(string|array $excludeTables): self
    {
        if ($this->includeTables !== []) {
            throw CannotSetParameter::conflictingParameters('excludeTables', 'includeTables');
        }

        if (! is_array($excludeTables)) {
            $excludeTables = explode(', ', $excludeTables);
        }

        $this->excludeTables = $excludeTables;

        return $this;
    }

    public function addExtraOption(string $extraOption): self
    {
        if ($extraOption !== '' && $extraOption !== '0') {
            $this->extraOptions[] = $extraOption;
        }

        return $this;
    }

    public function useInserts(): self
    {
        $this->useInserts = true;

        return $this;
    }

    public function doNotCreateTables(): self
    {
        $this->createTables = false;

        return $this;
    }

    public function doNotDumpData(): self
    {
        $this->includeData = false;

        return $this;
    }

    public function dumpToFile(string $dumpFile): void
    {
        $this->guardAgainstIncompleteCredentials();

        $tempFileHandle = tmpfile();
        $this->setTempFileHandle($tempFileHandle);

        $process = $this->getProcess($dumpFile);

        $process->run();

        $this->checkIfDumpWasSuccessFul($process, $dumpFile);
    }

    /**
     * Build the pg_dump invocation as an argument list.
     *
     * Never assemble this into a shell string: table names and hosts come from
     * configuration and command line options, and a shell would happily execute
     * whatever they contain.
     *
     * @return array<int, string>
     */
    public function getDumpCommand(string $dumpFile): array
    {
        $command = [
            $this->dumpBinaryPath.'pg_dump',
            '--username', $this->userName,
            '--host', $this->host,
            '--port', (string) $this->port,
        ];

        if ($this->useInserts) {
            $command[] = '--inserts';
        }

        if (! $this->createTables) {
            $command[] = '--data-only';
        }

        if (! $this->includeData) {
            $command[] = '--schema-only';
        }

        foreach ($this->extraOptions as $extraOption) {
            $command[] = $extraOption;
        }

        foreach ($this->includeTables as $includeTable) {
            $command[] = '--table';
            $command[] = $includeTable;
        }

        foreach ($this->excludeTables as $excludeTable) {
            $command[] = '--exclude-table';
            $command[] = $excludeTable;
        }

        // Writing to a seekable file rather than redirecting stdout lets pg_dump record
        // the data offsets in the archive, which is what `pg_restore --jobs` needs to
        // restore in parallel.
        $command[] = '--file';
        $command[] = $dumpFile;

        return $command;
    }

    public function getContentsOfCredentialsFile(): string
    {
        $contents = [
            $this->escapeCredentialEntry($this->host),
            $this->escapeCredentialEntry((string) $this->port),
            $this->escapeCredentialEntry($this->dbName),
            $this->escapeCredentialEntry($this->userName),
            $this->escapeCredentialEntry($this->password),
        ];

        return implode(':', $contents);
    }

    protected function escapeCredentialEntry(string $entry): string
    {
        $entry = str_replace('\\', '\\\\', $entry);

        return str_replace(':', '\\:', $entry);
    }

    public function guardAgainstIncompleteCredentials(): void
    {
        foreach (['userName', 'dbName', 'host'] as $requiredProperty) {
            if (empty($this->$requiredProperty)) {
                throw CannotStartDump::emptyParameter($requiredProperty);
            }
        }
    }

    protected function getEnvironmentVariablesForDumpCommand(string $temporaryCredentialsFile): array
    {
        return [
            'PGPASSFILE' => $temporaryCredentialsFile,
            'PGDATABASE' => $this->dbName,
        ];
    }

    public function getProcess(string $dumpFile): Process
    {
        $command = $this->getDumpCommand($dumpFile);

        fwrite($this->getTempFileHandle(), $this->getContentsOfCredentialsFile());
        $temporaryCredentialsFile = stream_get_meta_data($this->getTempFileHandle())['uri'];

        $envVars = $this->getEnvironmentVariablesForDumpCommand($temporaryCredentialsFile);

        return new Process($command, null, $envVars, null, $this->timeout);
    }

    /**
     * @return false|resource
     */
    public function getTempFileHandle()
    {
        return $this->tempFileHandle;
    }

    /**
     * @param  false|resource  $tempFileHandle
     */
    public function setTempFileHandle($tempFileHandle): void
    {
        $this->tempFileHandle = $tempFileHandle;
    }

    public function checkIfDumpWasSuccessFul(Process $process, string $outputFile): void
    {
        if (! $process->isSuccessful()) {
            throw DumpFailed::processDidNotEndSuccessfully($process);
        }

        if (! file_exists($outputFile)) {
            throw DumpFailed::dumpfileWasNotCreated($process);
        }

        if (filesize($outputFile) === 0) {
            throw DumpFailed::dumpfileWasEmpty($process);
        }
    }
}
