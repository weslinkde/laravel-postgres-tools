<?php

namespace App\Aliases\weslinkde\LaravelPostgresTools\src;

use Exception;
use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class PostgresSnapshot extends Snapshot
{
    public function streamToLocalFile($sourceDisk, $sourcePath, $localFilePath)
    {
        // Open a read stream from the source disk
        $readStream = $sourceDisk->readStream($sourcePath);

        if ($readStream === false) {
            throw new Exception("Failed to open stream for {$sourcePath} on {$sourceDisk} disk.");
        }

        // Open a file handle for writing locally
        $localFile = fopen($localFilePath, 'w');

        if ($localFile === false) {
            fclose($readStream); // Close the read stream if local file open fails
            throw new Exception("Failed to open local file {$localFilePath} for writing.");
        }

        // Write the contents from the read stream to the local file in chunks
        while (! feof($readStream)) {
            // Read a chunk of data and write it to the local file
            fwrite($localFile, fread($readStream, 8192));
        }

        // Close both file handles
        fclose($readStream);
        fclose($localFile);
    }

    /**
     * @throws CannotCreateConnection
     * @throws Exception
     */
    public function load(?string $connectionName = null, bool $dropTables = true): void
    {
        event(new LoadingSnapshot($this));

        if (! $connectionName) {
            $connectionName = config('database.default');
        }

        if ($dropTables) {
            $this->dropAllCurrentTables();
        }

        $postgresHelper = PostgresHelper::createForConnection($connectionName);
        $isDiskLocal = $this->disk->getConfig()['driver'] === 'local';

        if ($isDiskLocal) {
            $dbDumpFilePath = $this->disk->path($this->fileName);
        } else {
            $dbDumpDirectory = rtrim(config('db-snapshots.temporary_directory_path'), '/') . '/';
            $dbDumpFilePath = $dbDumpDirectory . $this->fileName;
            if (! file_exists($dbDumpDirectory)) {
                mkdir($dbDumpDirectory, 0777, true);
            }
            $this->streamToLocalFile($this->disk, $this->fileName, $dbDumpFilePath);
        }

        spin(
            fn () => $postgresHelper->restoreSnapshot($dbDumpFilePath),
            'Importing snapshot ' . $this->name . '...'
        );

        if (! $isDiskLocal && file_exists($dbDumpFilePath)) {
            unlink($dbDumpFilePath);
        }

        event(new LoadedSnapshot($this));
    }
}
