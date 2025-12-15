<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Weslinkde\PostgresTools\Commands\Concerns\AsksForSnapshotName;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class DeleteSnapshot extends Command
{
    use AsksForSnapshotName;

    protected $signature = 'weslink:snapshot:delete {name?}';

    protected $description = 'Delete a snapshot.';

    public function handle(): void
    {
        if (app(PostgresSnapshotRepository::class)->getAll()->isEmpty()) {
            $this->warn('No snapshots found. Run `snapshot:create` to create snapshots.');

            return;
        }

        $name = $this->argument('name') ?: $this->askForSnapshotName();

        $snapshot = app(PostgresSnapshotRepository::class)->findByName($name);
        if (! $snapshot) {
            warning("Snapshot `{$name}` does not exist!");

            return;
        }

        $snapshot->delete();

        info("Snapshot `{$snapshot->name}` deleted!");
    }
}
