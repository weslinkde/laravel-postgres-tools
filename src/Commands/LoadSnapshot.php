<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Weslinkde\PostgresTools\Commands\Concerns\AsksForSnapshotName;
use Weslinkde\PostgresTools\PostgresSnapshot;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;

use function Laravel\Prompts\select;

class LoadSnapshot extends Command
{
    use AsksForSnapshotName;
    use ConfirmableTrait;

    protected $signature = 'weslink:snapshot:load {name?} {--connection=} {--force} --disk {--latest} {--drop-tables=1}';

    protected $description = 'Load up a snapshot.';

    public function handle()
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

        /** @var \Spatie\DbSnapshots\Snapshot $snapshot */
        $snapshot = app(PostgresSnapshotRepository::class)->findByName($name);

        if (! $snapshot) {
            $this->warn("Snapshot `{$name}` does not exist!");

            return;
        }

        $snapshot->load($this->option('connection'), (bool) $this->option('drop-tables'));

        $this->info("Snapshot `{$name}` loaded!");
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
