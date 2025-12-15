<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Weslinkde\PostgresTools\Commands\Concerns\AsksForSnapshotName;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Snapshot;

use function Laravel\Prompts\select;

class LoadSnapshot extends Command
{
    use AsksForSnapshotName;
    use ConfirmableTrait;

    protected $signature = 'weslink:snapshot:load {name?} {--connection=} {--database=} {--force} --disk {--latest} {--drop-tables=1}';

    protected $description = 'Load up a snapshot.';

    public function handle(): void
    {
        $snapShots = app(PostgresSnapshotRepository::class)->getAll();

        if ($snapShots->isEmpty()) {
            $this->warn('No snapshots found. Run `snapshot:create` first to create snapshots.');

            return;
        }

        if (! $this->confirmToProceed()) {
            return;
        }

        $useLatestSnapshot = $this->option('latest') ?: false;

        $name = $useLatestSnapshot
            ? $snapShots->first()->name
            : ($this->argument('name') ?: $this->askForSnapshotName());

        $snapshot = app(PostgresSnapshotRepository::class)->findByName($name);

        if (! $snapshot) {
            $this->warn("Snapshot `{$name}` does not exist!");

            return;
        }

        $snapshot->load(
            $this->option('connection'),
            (bool) $this->option('drop-tables'),
            $this->option('database')
        );

        $this->info("Snapshot `{$name}` loaded!");
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
