<?php

namespace Weslinkde\PostgresTools\Commands\Concerns;

use Weslinkde\PostgresTools\PostgresSnapshot;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;

use function Laravel\Prompts\select;

trait AsksForSnapshotName
{
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
