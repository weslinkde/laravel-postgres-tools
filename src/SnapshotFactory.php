<?php

namespace Weslinkde\PostgresTools;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Weslinkde\PostgresTools\Dumper\PostgresDumper;
use Weslinkde\PostgresTools\Events\CreatedSnapshot;
use Weslinkde\PostgresTools\Events\CreatingSnapshot;
use Weslinkde\PostgresTools\Exceptions\CannotCreateDisk;
use Weslinkde\PostgresTools\Support\TemporaryDirectory;

class SnapshotFactory
{
    public function __construct(
        protected Factory $filesystemFactory,
    ) {
        //
    }

    /**
     * Create a new snapshot.
     *
     * @param  string  $snapshotName  The name of the snapshot
     * @param  string  $diskName  The name of the filesystem disk
     * @param  string  $connectionName  The database connection name
     * @param  bool  $compress  Whether to compress the snapshot (not used for PostgreSQL custom format)
     * @param  array|null  $tables  Tables to include in the snapshot
     * @param  array|null  $exclude  Tables to exclude from the snapshot
     * @return Snapshot
     */
    public function create(
        string $snapshotName,
        string $diskName,
        string $connectionName,
        bool $compress = false,
        ?array $tables = null,
        ?array $exclude = null
    ): Snapshot {
        $disk = $this->getDisk($diskName);

        $fileName = $snapshotName . '.sql';
        $fileName = pathinfo($fileName, PATHINFO_BASENAME);

        $extraOptions = $this->getExtraOptions();

        event(new CreatingSnapshot(
            $fileName,
            $disk,
            $connectionName,
            $tables,
            $exclude,
            $extraOptions
        ));

        $this->createDump($connectionName, $fileName, $disk, $tables, $exclude, $extraOptions);

        $snapshot = new Snapshot($disk, $fileName);

        event(new CreatedSnapshot($snapshot));

        return $snapshot;
    }

    /**
     * Get the filesystem disk instance.
     *
     * @throws CannotCreateDisk
     */
    protected function getDisk(string $diskName): FilesystemAdapter
    {
        if (is_null(config("filesystems.disks.{$diskName}"))) {
            throw CannotCreateDisk::diskNotDefined($diskName);
        }

        $disk = $this->filesystemFactory->disk($diskName);

        if (! $disk instanceof FilesystemAdapter) {
            throw new \RuntimeException("Disk {$diskName} is not a FilesystemAdapter instance.");
        }

        return $disk;
    }

    /**
     * Get the PostgreSQL dumper instance for the connection.
     */
    protected function getDbDumper(string $connectionName): PostgresDumper
    {
        $dbConfig = config("database.connections.{$connectionName}");

        if (is_null($dbConfig)) {
            throw new \RuntimeException("Connection [{$connectionName}] does not exist.");
        }

        $fallback = $dbConfig['read']['host'] ?? $dbConfig['host'];
        $dbHost = $dbConfig['read']['host'][0] ?? $fallback;
        $dbName = $dbConfig['connect_via_database'] ?? $dbConfig['database'];

        $dbDumper = PostgresDumper::create()
            ->setHost($dbHost ?? '')
            ->setDbName($dbName)
            ->setUserName($dbConfig['username'] ?? '')
            ->setPassword($dbConfig['password'] ?? '');

        if (isset($dbConfig['port'])) {
            $dbDumper->setPort($dbConfig['port']);
        }

        return $dbDumper;
    }

    /**
     * Get extra options from configuration.
     */
    protected function getExtraOptions(): array
    {
        $extraOptionsString = config('postgres-tools.addExtraOption', '');

        if (empty($extraOptionsString)) {
            return [];
        }

        return array_filter(explode(' ', $extraOptionsString));
    }

    /**
     * Create the database dump.
     */
    protected function createDump(
        string $connectionName,
        string $fileName,
        FilesystemAdapter $disk,
        ?array $tables = null,
        ?array $exclude = null,
        array $extraOptions = []
    ): void {
        $directory = (new TemporaryDirectory(config('postgres-tools.temporary_directory_path')))->create();

        $dumpPath = $directory->path($fileName);

        $dbDumper = $this->getDbDumper($connectionName);

        if (is_array($tables)) {
            $dbDumper->includeTables($tables);
        }

        if (is_array($exclude)) {
            $dbDumper->excludeTables($exclude);
        }

        foreach ($extraOptions as $extraOption) {
            $dbDumper->addExtraOption($extraOption);
        }

        $dbDumper->dumpToFile($dumpPath);

        $file = fopen($dumpPath, 'r');

        $disk->put($fileName, $file);

        if (is_resource($file)) {
            fclose($file);
        }

        gc_collect_cycles();

        $directory->delete();
    }
}
