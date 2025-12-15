<?php

use Weslinkde\PostgresTools\PostgresSnapshotRepository;
use Weslinkde\PostgresTools\Snapshot;

beforeEach(function () {
    // Mock the snapshot repository
    $this->snapshotRepository = Mockery::mock(PostgresSnapshotRepository::class);
    $this->app->instance(PostgresSnapshotRepository::class, $this->snapshotRepository);
});

it('displays a warning when no snapshots exist', function () {
    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect());

    $this->artisan('weslink:snapshot:load')
        ->expectsOutput('No snapshots found. Run `snapshot:create` first to create snapshots.')
        ->assertExitCode(0);
});

it('displays a warning when snapshot does not exist', function () {
    $snapshot = Mockery::mock(Snapshot::class);
    $snapshot->name = 'existing-snapshot';

    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect([$snapshot]));

    $this->snapshotRepository
        ->shouldReceive('findByName')
        ->with('non-existent-snapshot')
        ->once()
        ->andReturn(null);

    $this->artisan('weslink:snapshot:load', ['name' => 'non-existent-snapshot', '--force' => true])
        ->expectsOutput('Snapshot `non-existent-snapshot` does not exist!')
        ->assertExitCode(0);
});

it('loads a snapshot successfully', function () {
    $snapshot = Mockery::mock(Snapshot::class);
    $snapshot->name = 'test-snapshot';

    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect([$snapshot]));

    $this->snapshotRepository
        ->shouldReceive('findByName')
        ->with('test-snapshot')
        ->once()
        ->andReturn($snapshot);

    $snapshot
        ->shouldReceive('load')
        ->with(null, true)
        ->once();

    $this->artisan('weslink:snapshot:load', ['name' => 'test-snapshot', '--force' => true])
        ->expectsOutput('Snapshot `test-snapshot` loaded!')
        ->assertExitCode(0);
});

it('loads the latest snapshot when --latest option is provided', function () {
    $snapshot1 = Mockery::mock(Snapshot::class);
    $snapshot1->name = 'latest-snapshot';

    $snapshot2 = Mockery::mock(Snapshot::class);
    $snapshot2->name = 'older-snapshot';

    // getAll() is called twice: once in handle() and once in askForSnapshotName() (but won't be called with --latest)
    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect([$snapshot1, $snapshot2]));

    $this->snapshotRepository
        ->shouldReceive('findByName')
        ->with('latest-snapshot')
        ->once()
        ->andReturn($snapshot1);

    $snapshot1
        ->shouldReceive('load')
        ->with(null, true)
        ->once();

    $this->artisan('weslink:snapshot:load', ['--latest' => true, '--force' => true])
        ->expectsOutput('Snapshot `latest-snapshot` loaded!')
        ->assertExitCode(0);
});

it('loads snapshot with custom connection', function () {
    $snapshot = Mockery::mock(Snapshot::class);
    $snapshot->name = 'test-snapshot';

    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect([$snapshot]));

    $this->snapshotRepository
        ->shouldReceive('findByName')
        ->with('test-snapshot')
        ->once()
        ->andReturn($snapshot);

    $snapshot
        ->shouldReceive('load')
        ->with('custom-connection', true)
        ->once();

    $this->artisan('weslink:snapshot:load', [
        'name' => 'test-snapshot',
        '--connection' => 'custom-connection',
        '--force' => true,
    ])
        ->expectsOutput('Snapshot `test-snapshot` loaded!')
        ->assertExitCode(0);
});

it('loads snapshot without dropping tables when --drop-tables=0', function () {
    $snapshot = Mockery::mock(Snapshot::class);
    $snapshot->name = 'test-snapshot';

    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect([$snapshot]));

    $this->snapshotRepository
        ->shouldReceive('findByName')
        ->with('test-snapshot')
        ->once()
        ->andReturn($snapshot);

    $snapshot
        ->shouldReceive('load')
        ->with(null, false)
        ->once();

    $this->artisan('weslink:snapshot:load', [
        'name' => 'test-snapshot',
        '--drop-tables' => '0',
        '--force' => true,
    ])
        ->expectsOutput('Snapshot `test-snapshot` loaded!')
        ->assertExitCode(0);
});

it('accepts --force flag to skip confirmation', function () {
    $snapshot = Mockery::mock(Snapshot::class);
    $snapshot->name = 'test-snapshot';

    $this->snapshotRepository
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(collect([$snapshot]));

    $this->snapshotRepository
        ->shouldReceive('findByName')
        ->with('test-snapshot')
        ->once()
        ->andReturn($snapshot);

    $snapshot
        ->shouldReceive('load')
        ->with(null, true)
        ->once();

    // --force flag should skip any confirmation prompts
    $this->artisan('weslink:snapshot:load', ['name' => 'test-snapshot', '--force' => true])
        ->expectsOutput('Snapshot `test-snapshot` loaded!')
        ->assertExitCode(0);
});
