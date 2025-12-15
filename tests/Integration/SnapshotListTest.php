<?php

use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    DB::statement('CREATE TABLE IF NOT EXISTS snapshot_list_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('snapshot_list_test')->insert(['data' => 'test_data']);
});

afterEach(function (): void {
    DB::statement('DROP TABLE IF EXISTS snapshot_list_test');
});

it('displays warning when no snapshots exist', function (): void {
    $this->artisan('weslink:snapshot:list')
        ->expectsOutputToContain('No snapshots found')
        ->assertExitCode(0);
});

it('lists existing snapshots', function (): void {
    $snapshotName = $this->generateTestSnapshotName('list_test');

    // Create a snapshot
    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // List snapshots - should show the created snapshot
    $this->artisan('weslink:snapshot:list')
        ->assertExitCode(0);

    // Verify the snapshot exists
    expect($this->snapshotExists($snapshotName))->toBeTrue();
});

it('lists multiple snapshots', function (): void {
    $snapshot1 = $this->generateTestSnapshotName('list_multi_1');
    $snapshot2 = $this->generateTestSnapshotName('list_multi_2');

    // Create two snapshots
    $this->artisan('weslink:snapshot:create', ['name' => $snapshot1])
        ->assertExitCode(0);

    $this->artisan('weslink:snapshot:create', ['name' => $snapshot2])
        ->assertExitCode(0);

    // List snapshots
    $this->artisan('weslink:snapshot:list')
        ->assertExitCode(0);

    // Verify both snapshots exist
    expect($this->snapshotExists($snapshot1))->toBeTrue();
    expect($this->snapshotExists($snapshot2))->toBeTrue();
});
