<?php

namespace Weslinkde\PostgresTools;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;

class PostgresSnapshotRepository
{
    protected FilesystemAdapter $disk;

    public function __construct(Factory $filesystemFactory, string $diskName)
    {
        $disk = $filesystemFactory->disk($diskName);

        if (! $disk instanceof FilesystemAdapter) {
            throw new \RuntimeException("Disk {$diskName} is not a FilesystemAdapter instance.");
        }

        $this->disk = $disk;
    }

    /**
     * @return Collection<int, Snapshot>
     */
    public function getAll(): Collection
    {
        return collect($this->disk->allFiles())
            ->filter(function (string $fileName): bool {
                $pathinfo = pathinfo($fileName);

                if (($pathinfo['extension'] ?? null) === 'gz') {
                    $fileName = $pathinfo['filename'];
                }

                return pathinfo($fileName, PATHINFO_EXTENSION) === 'sql';
            })
            ->map(
                fn (string $fileName): \Weslinkde\PostgresTools\Snapshot => new Snapshot($this->disk, $fileName)
            )
            ->sortByDesc(fn (Snapshot $snapshot): string => $snapshot->createdAt()->toDateTimeString());
    }

    public function findByName(string $name): ?Snapshot
    {
        return $this->getAll()->first(fn (Snapshot $snapshot): bool => $snapshot->name === $name);
    }
}
