<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;

class CloneDatabase extends Command
{
    protected $signature = 'weslink:database:clone {databaseName} {newDatabaseName}';

    protected $description = 'Clones a database.';

    public function handle(): int
    {
        $databaseName = $this->argument('databaseName');
        $newDatabaseName = $this->argument('newDatabaseName');

        $connectionName = config('postgres-tools.default_connection', config('database.default'));

        try {
            $postgresHelper = PostgresHelper::createForConnection($connectionName)->setName($databaseName);

            // Create a snapshot of the database
            $snapshot = spin(fn () => $postgresHelper->createSnapshot('temp-snapshot'), 'Creating snapshot...');

            try {
                $snapshotPath = $snapshot->disk->path($snapshot->fileName);

                // Make sure the local client can read the archive before creating anything
                $postgresHelper->assertSnapshotIsRestorable($snapshotPath);

                // Create a new database
                $postgresHelper->setName($newDatabaseName);
                spin(fn (): Process|bool => $postgresHelper->createDatabase(), 'Creating new database...');

                // Load the snapshot into the new database
                spin(fn (): Process => $postgresHelper->restoreSnapshot($snapshotPath), 'Loading snapshot...');
            } finally {
                // Delete the snapshot, also when the clone failed half way through
                $snapshot->delete();
            }
        } catch (\Exception $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Database with name `{$newDatabaseName}` was created!");

        return self::SUCCESS;
    }
}
