<?php

namespace Weslinkde\PostgresTools;

use Carbon\Carbon;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\Events\DeletedSnapshot;
use Weslinkde\PostgresTools\Events\DeletingSnapshot;
use Weslinkde\PostgresTools\Events\LoadedSnapshot;
use Weslinkde\PostgresTools\Events\LoadingSnapshot;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class Snapshot
{
    public string $name;

    public ?string $compressionExtension = null;

    public function __construct(public Disk $disk, public string $fileName)
    {
        $pathinfo = pathinfo($this->fileName);

        if (isset($pathinfo['extension']) && $pathinfo['extension'] === 'gz') {
            $this->compressionExtension = $pathinfo['extension'];
            $this->fileName = $pathinfo['filename'];
        }

        $this->name = pathinfo($this->fileName, PATHINFO_FILENAME);
    }

    /**
     * Stream a file from a non-local disk to a local file path.
     *
     * @throws Exception
     */
    protected function streamToLocalFile(Disk $sourceDisk, string $sourcePath, string $localFilePath): void
    {
        $expectedSize = $sourceDisk->size($sourcePath);

        $readStream = $sourceDisk->readStream($sourcePath);

        if (! is_resource($readStream)) {
            throw new Exception("Failed to open stream for {$sourcePath} on disk.");
        }

        $localFile = fopen($localFilePath, 'w');

        if ($localFile === false) {
            fclose($readStream);
            throw new Exception("Failed to open local file {$localFilePath} for writing.");
        }

        $writtenBytes = 0;

        try {
            while (! feof($readStream)) {
                $chunk = fread($readStream, 8192);

                if ($chunk === false) {
                    throw new Exception("Failed to read {$sourcePath} from disk.");
                }

                if ($chunk === '') {
                    continue;
                }

                $written = fwrite($localFile, $chunk);

                // A short write means the local disk is full or the file handle died.
                // Carrying on would silently produce a truncated dump.
                if ($written === false || $written !== strlen($chunk)) {
                    throw new Exception("Failed to write {$localFilePath}; the local disk may be full.");
                }

                $writtenBytes += $written;
            }
        } finally {
            fclose($readStream);
            fclose($localFile);
        }

        if ($writtenBytes !== $expectedSize) {
            throw new Exception(
                "Snapshot {$sourcePath} was streamed incompletely: expected {$expectedSize} bytes, got {$writtenBytes}."
            );
        }
    }

    /**
     * Load the snapshot into the database using pg_restore.
     *
     * @param  string|null  $connectionName  The database connection to use
     * @param  bool  $dropTables  Whether to drop existing tables before loading
     * @param  string|null  $database  Override the database name (useful for multi-tenancy)
     *
     * @throws CannotCreateConnection
     * @throws Exception
     */
    public function load(?string $connectionName = null, bool $dropTables = true, ?string $database = null): void
    {
        event(new LoadingSnapshot($this));

        if (! $connectionName) {
            $connectionName = config('database.default');
        }

        $postgresHelper = PostgresHelper::createForConnection($connectionName);

        // The dump has to be on local disk before anything can be verified, and it has to be
        // verified before anything is dropped - otherwise a broken snapshot leaves an empty
        // database behind.
        $isDiskLocal = $this->disk->getConfig()['driver'] === 'local';
        $dbDumpDirectory = rtrim((string) config('postgres-tools.temporary_directory_path'), '/').'/';

        $dbDumpFilePath = $isDiskLocal
            ? $this->disk->path($this->fileName)
            : $dbDumpDirectory.$this->fileName;

        try {
            if (! $isDiskLocal) {
                if (! file_exists($dbDumpDirectory)) {
                    mkdir($dbDumpDirectory, 0777, true);
                }

                $this->streamToLocalFile($this->disk, $this->fileName, $dbDumpFilePath);
            }

            $postgresHelper->assertSnapshotIsRestorable($dbDumpFilePath);

            if ($database !== null) {
                $postgresHelper->setName($database);
                $postgresHelper->createDatabase();
            } elseif ($dropTables) {
                $this->dropAllCurrentTables($connectionName);
            }

            spin(
                fn (): Process => $postgresHelper->restoreSnapshot($dbDumpFilePath),
                'Importing snapshot '.$this->name.'...'
            );
        } finally {
            if (! $isDiskLocal && file_exists($dbDumpFilePath)) {
                unlink($dbDumpFilePath);
            }
        }

        event(new LoadedSnapshot($this));
    }

    /**
     * Delete the snapshot file from the disk.
     */
    public function delete(): void
    {
        event(new DeletingSnapshot($this));

        $this->disk->delete($this->fileName);

        event(new DeletedSnapshot($this->fileName, $this->disk));
    }

    /**
     * Get the size of the snapshot file in bytes.
     */
    public function size(): int
    {
        return $this->disk->size($this->fileName);
    }

    /**
     * Get the creation timestamp of the snapshot.
     */
    public function createdAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->disk->lastModified($this->fileName));
    }

    /**
     * Drop all current tables on the connection the snapshot is loaded into.
     */
    protected function dropAllCurrentTables(?string $connectionName = null): void
    {
        $connectionName ??= DB::getDefaultConnection();

        DB::connection($connectionName)
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect($connectionName);
    }
}
