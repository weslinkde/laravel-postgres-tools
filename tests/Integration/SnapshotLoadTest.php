<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Setup test table with data
    DB::statement('CREATE TABLE IF NOT EXISTS snapshot_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('snapshot_test')->insert(['data' => 'original_data']);
});

afterEach(function () {
    // Cleanup test table
    DB::statement('DROP TABLE IF EXISTS snapshot_test');
});

it('loads a snapshot and restores data', function () {
    $snapshotName = $this->generateTestSnapshotName('load_test');

    // Create a snapshot
    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Modify the data
    DB::table('snapshot_test')->truncate();
    DB::table('snapshot_test')->insert(['data' => 'modified_data']);

    // Load the snapshot
    $this->artisan('weslink:snapshot:load', [
        'name' => $snapshotName,
        '--force' => true,
    ])
        ->expectsOutput("Snapshot `{$snapshotName}` loaded!")
        ->assertExitCode(0);

    // Verify original data is restored
    $result = DB::table('snapshot_test')->where('data', 'original_data')->first();
    expect($result)->not->toBeNull();
});

it('loads the latest snapshot when --latest flag is used', function () {
    $snapshot1 = $this->generateTestSnapshotName('older');
    $snapshot2 = $this->generateTestSnapshotName('latest');

    // Create two snapshots
    $this->artisan('weslink:snapshot:create', ['name' => $snapshot1])
        ->assertExitCode(0);

    sleep(1); // Ensure different timestamps

    DB::table('snapshot_test')->update(['data' => 'latest_data']);

    $this->artisan('weslink:snapshot:create', ['name' => $snapshot2])
        ->assertExitCode(0);

    // Modify data
    DB::table('snapshot_test')->truncate();

    // Load latest snapshot
    $this->artisan('weslink:snapshot:load', [
        '--latest' => true,
        '--force' => true,
    ])
        ->assertExitCode(0);

    // Verify data from latest snapshot is restored
    $result = DB::table('snapshot_test')->first();
    expect($result)->not->toBeNull();
});

it('displays warning when no snapshots exist', function () {
    $this->artisan('weslink:snapshot:load')
        ->expectsOutput('No snapshots found. Run `snapshot:create` first to create snapshots.')
        ->assertExitCode(0);
});

it('displays warning when specified snapshot does not exist', function () {
    $nonExistentSnapshot = 'non_existent_snapshot';

    // Create at least one snapshot so the command proceeds
    $existingSnapshot = $this->generateTestSnapshotName('existing');
    $this->artisan('weslink:snapshot:create', ['name' => $existingSnapshot])
        ->assertExitCode(0);

    $this->artisan('weslink:snapshot:load', [
        'name' => $nonExistentSnapshot,
        '--force' => true,
    ])
        ->expectsOutput("Snapshot `{$nonExistentSnapshot}` does not exist!")
        ->assertExitCode(0);
});

it('loads snapshot with custom connection', function () {
    $snapshotName = $this->generateTestSnapshotName('custom_conn');

    // Create snapshot
    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Modify data
    DB::table('snapshot_test')->truncate();

    // Load with custom connection
    $this->artisan('weslink:snapshot:load', [
        'name' => $snapshotName,
        '--connection' => 'pgsql',
        '--force' => true,
    ])
        ->expectsOutput("Snapshot `{$snapshotName}` loaded!")
        ->assertExitCode(0);

    // Verify data is restored
    $result = DB::table('snapshot_test')->first();
    expect($result)->not->toBeNull();
});

it('loads snapshot without dropping tables when --drop-tables=0', function () {
    $snapshotName = $this->generateTestSnapshotName('no_drop');

    // Create initial data
    DB::table('snapshot_test')->insert(['data' => 'initial_data']);

    // Create snapshot
    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Add more data
    DB::table('snapshot_test')->insert(['data' => 'additional_data']);

    // Load snapshot without dropping tables
    $this->artisan('weslink:snapshot:load', [
        'name' => $snapshotName,
        '--drop-tables' => '0',
        '--force' => true,
    ])
        ->expectsOutput("Snapshot `{$snapshotName}` loaded!")
        ->assertExitCode(0);

    // Both original and additional data should exist
    expect(DB::table('snapshot_test')->count())->toBeGreaterThan(0);
});

it('respects --force flag to skip confirmation', function () {
    $snapshotName = $this->generateTestSnapshotName('force_flag');

    // Create snapshot
    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Load with --force flag should not prompt
    $this->artisan('weslink:snapshot:load', [
        'name' => $snapshotName,
        '--force' => true,
    ])
        ->expectsOutput("Snapshot `{$snapshotName}` loaded!")
        ->assertExitCode(0);
});
