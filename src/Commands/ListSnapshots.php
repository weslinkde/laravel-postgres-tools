<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Snapshot;
use Weslinkde\PostgresTools\Support\Format;

use function Laravel\Prompts\table;

class ListSnapshots extends Command
{
    protected $signature = 'weslink:snapshot:list';

    protected $description = 'List all available snapshots.';

    public function handle(): void
    {
        $snapshots = app(PostgresSnapshotRepository::class)->getAll();

        if ($snapshots->isEmpty()) {
            $this->warn('No snapshots found. Run `weslink:snapshot:create` to create snapshots.');

            return;
        }

        $rows = $snapshots->map(fn (Snapshot $snapshot): array => [
            $snapshot->name,
            Format::humanReadableSize($snapshot->size()),
            $snapshot->createdAt()->format('Y-m-d H:i'),
            $snapshot->disk->path($snapshot->fileName),
        ])->toArray();

        table(['Name', 'Size', 'Created', 'Path'], $rows);
    }
}
