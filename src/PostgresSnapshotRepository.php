<?php

namespace Weslinkde\PostgresTools;

use Illuminate\Support\Collection;
use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

class PostgresSnapshotRepository extends SnapshotRepository
{
    /**
     * @return Collection<int, PostgresSnapshot>
     */
    public function getAll(): Collection
    {
        return collect($this->disk->allFiles())
            ->filter(function (string $fileName) {
                $pathinfo = pathinfo($fileName);

                if (($pathinfo['extension'] ?? null) === 'gz') {
                    $fileName = $pathinfo['filename'];
                }

                return pathinfo($fileName, PATHINFO_EXTENSION) === 'sql';
            })
            ->map(
                /** @phpstan-ignore-next-line */
                fn (string $fileName) => new PostgresSnapshot($this->disk, $fileName)
            )
            ->sortByDesc(fn (PostgresSnapshot $snapshot) => $snapshot->createdAt()->toDateTimeString());
    }

    public function findByName(string $name): ?Snapshot
    {
        return $this->getAll()->first(fn (PostgresSnapshot $snapshot) => $snapshot->name === $name);
    }
}
