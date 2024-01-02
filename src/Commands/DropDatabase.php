<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\PostgresSnapshot;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class DropDatabase extends Command
{
    use ConfirmableTrait;

    protected $signature = 'weslink:database:drop {name}';

    protected $description = 'Drops a database.';

    public function handle()
    {
        $databaseName = $this->argument('name');

        $connectionName = config('db-snapshots.default_connection')
            ?? config('database.default');

        if (! $this->confirmToProceed()) {
            return;
        }

        $postgresHelper = PostgresHelper::createForConnection($connectionName)->setName($databaseName);

        /** @var Process|bool $result */
        $result = spin(fn () => $postgresHelper->dropDatabase(), 'Dropping database...');

        if ($result === false || ! $result->isSuccessful()) {
            $this->error('Failed to drop database.');

            return;
        }

        $this->info("Database with name `{$databaseName}` was dropped!");
    }

    public function askForSnapshotName(): string
    {
        $snapShots = app(PostgresSnapshotRepository::class)->getAll();

        $names = $snapShots->map(fn (PostgresSnapshot $snapshot) => $snapshot->name)
            ->values()->toArray();

        return select(
            'Which snapshot should be loaded?',
            $names,
        );
    }
}
