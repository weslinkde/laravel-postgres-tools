<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Weslinkde\PostgresTools\Commands\Concerns\AsksForSnapshotName;
use Weslinkde\PostgresTools\Exceptions\ProcessFailed;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Snapshot;

use function Laravel\Prompts\select;

class LoadSnapshot extends Command
{
    use AsksForSnapshotName;
    use ConfirmableTrait;

    protected $signature = 'weslink:snapshot:load {name?} {--connection=} {--database=} {--force} --disk {--latest} {--drop-tables=1}';

    protected $description = 'Load up a snapshot.';

    public function handle(): int
    {
        $snapShots = app(PostgresSnapshotRepository::class)->getAll();

        if ($snapShots->isEmpty()) {
            $this->warn('No snapshots found. Run `snapshot:create` first to create snapshots.');

            return self::FAILURE;
        }

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $useLatestSnapshot = $this->option('latest') ?: false;

        $name = $useLatestSnapshot
            ? $snapShots->first()->name
            : ($this->argument('name') ?: $this->askForSnapshotName());

        $snapshot = app(PostgresSnapshotRepository::class)->findByName($name);

        if (! $snapshot) {
            $this->warn("Snapshot `{$name}` does not exist!");

            return self::FAILURE;
        }

        try {
            $snapshot->load(
                $this->option('connection'),
                (bool) $this->option('drop-tables'),
                $this->option('database')
            );
        } catch (ProcessFailed $e) {
            // Never report success for a restore that did not happen - the target database
            // may well be empty at this point.
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Snapshot `{$name}` loaded!");

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
