<?php

use Illuminate\Support\Collection;
use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Snapshot;

beforeEach(function (): void {
    $this->mockRepository = Mockery::mock(PostgresSnapshotRepository::class);
    $this->app->instance(PostgresSnapshotRepository::class, $this->mockRepository);
});

it('displays a warning when no snapshots exist', function (): void {
    $this->mockRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(new Collection);

    $this->artisan('weslink:snapshot:list')
        ->expectsOutputToContain('No snapshots found')
        ->assertExitCode(0);
});

it('lists all snapshots in a table', function (): void {
    $mockDisk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $mockDisk->shouldReceive('path')
        ->with('snapshot1.sql')
        ->andReturn('/path/to/snapshot1.sql');
    $mockDisk->shouldReceive('path')
        ->with('snapshot2.sql')
        ->andReturn('/path/to/snapshot2.sql');

    $snapshot1 = Mockery::mock(Snapshot::class);
    $snapshot1->name = 'snapshot1';
    $snapshot1->fileName = 'snapshot1.sql';
    $snapshot1->disk = $mockDisk;
    $snapshot1->shouldReceive('size')->andReturn(1024 * 1024 * 100); // 100 MB
    $snapshot1->shouldReceive('createdAt')->andReturn(now()->subDay());

    $snapshot2 = Mockery::mock(Snapshot::class);
    $snapshot2->name = 'snapshot2';
    $snapshot2->fileName = 'snapshot2.sql';
    $snapshot2->disk = $mockDisk;
    $snapshot2->shouldReceive('size')->andReturn(1024 * 1024 * 50); // 50 MB
    $snapshot2->shouldReceive('createdAt')->andReturn(now());

    $this->mockRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(new Collection([$snapshot1, $snapshot2]));

    $this->artisan('weslink:snapshot:list')
        ->assertExitCode(0);
});
