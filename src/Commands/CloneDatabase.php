<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class CloneDatabase extends Command
{
    protected $signature = 'weslink:database:clone {databaseName} {newDatabaseName}';

    protected $description = 'Clones a database.';

    public function handle(): void
    {
        $databaseName = $this->argument('databaseName');
        $newDatabaseName = $this->argument('newDatabaseName');

        $connectionName = config('postgres-tools.default_connection', config('database.default'));

        $postgresHelper = PostgresHelper::createForConnection($connectionName)->setName($databaseName);

        // Create a snapshot of the database
        $snapshot = spin(fn () => $postgresHelper->createSnapshot('temp-snapshot'), 'Creating snapshot...');
        // Create a new database
        $postgresHelper->setName($newDatabaseName);
        spin(fn (): \Symfony\Component\Process\Process|bool => $postgresHelper->createDatabase(), 'Creating new database...');

        // Load the snapshot into the new database
        spin(fn (): \Symfony\Component\Process\Process => $postgresHelper->restoreSnapshot($snapshot->disk->path($snapshot->fileName)), 'Loading snapshot...');

        // Delete the snapshot
        $snapshot->delete();

        $this->info("Database with name `{$newDatabaseName}` was created!");
    }
}
