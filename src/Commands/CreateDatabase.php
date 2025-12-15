<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Snapshot;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class CreateDatabase extends Command
{
    use ConfirmableTrait;

    protected $signature = 'weslink:database:create {name}';

    protected $description = 'Creates a database.';

    public function handle(): void
    {
        $newDatabaseName = $this->argument('name');

        $connectionName = config('postgres-tools.default_connection', config('database.default'));

        try {
            $postgresHelper = PostgresHelper::createForConnection($connectionName)->setName($newDatabaseName);
        } catch (\Exception $e) {
            error($e->getMessage());

            return;
        }

        /** @var Process|bool $result */
        $result = spin(fn (): \Symfony\Component\Process\Process|bool => $postgresHelper->createDatabase(), 'Creating new database...');

        if ($result === false || ! $result->isSuccessful()) {
            $this->error('Failed to create database.');

            return;
        }

        $this->info("Database with name `{$newDatabaseName}` was created!");
    }

    public function askForSnapshotName(): string
    {
        $snapShots = app(PostgresSnapshotRepository::class)->getAll();

        $names = $snapShots->map(fn (Snapshot $snapshot): string => $snapshot->name)
            ->values()->toArray();

        return select(
            'Which snapshot should be loaded?',
            $names,
        );
    }
}
