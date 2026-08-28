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

class DropDatabase extends Command
{
    use ConfirmableTrait;

    protected $signature = 'weslink:database:drop {name}';

    protected $description = 'Drops a database.';

    public function handle(): int
    {
        $databaseName = $this->argument('name');

        $connectionName = config('postgres-tools.default_connection', config('database.default'));

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        try {
            $postgresHelper = PostgresHelper::createForConnection($connectionName)->setName($databaseName);

            /** @var Process|bool $result */
            $result = spin(fn (): Process|bool => $postgresHelper->dropDatabase(), 'Dropping database...');
        } catch (\Exception $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        if ($result === false) {
            $this->error("Database `{$databaseName}` does not exist.");

            return self::FAILURE;
        }

        $this->info("Database with name `{$databaseName}` was dropped!");

        return self::SUCCESS;
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
