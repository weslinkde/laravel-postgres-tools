<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Setup test table for creating snapshots
    DB::statement('CREATE TABLE IF NOT EXISTS snapshot_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('snapshot_test')->insert(['data' => 'test_data']);
});

afterEach(function () {
    // Cleanup test table
    DB::statement('DROP TABLE IF EXISTS snapshot_test');
});

it('deletes an existing snapshot', function () {
    $snapshotName = $this->generateTestSnapshotName('delete_test');

    // Create a snapshot
    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Verify snapshot exists
    expect($this->snapshotExists($snapshotName))->toBeTrue();

    // Delete the snapshot
    $this->artisan('weslink:snapshot:delete', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Verify snapshot is deleted
    expect($this->snapshotExists($snapshotName))->toBeFalse();
});

it('displays warning when no snapshots exist', function () {
    $this->artisan('weslink:snapshot:delete')
        ->expectsOutput('No snapshots found. Run `snapshot:create` to create snapshots.')
        ->assertExitCode(0);
});

it('handles deleting non-existent snapshot gracefully', function () {
    $nonExistentSnapshot = 'non_existent_snapshot';

    // Create at least one snapshot so command proceeds past empty check
    $existingSnapshot = $this->generateTestSnapshotName('existing');
    $this->artisan('weslink:snapshot:create', ['name' => $existingSnapshot])
        ->assertExitCode(0);

    // Try to delete a non-existent snapshot - should exit gracefully
    $this->artisan('weslink:snapshot:delete', ['name' => $nonExistentSnapshot])
        ->assertExitCode(0);

    // Original snapshot should still exist
    expect($this->snapshotExists($existingSnapshot))->toBeTrue();
});

it('deletes multiple snapshots sequentially', function () {
    $snapshot1 = $this->generateTestSnapshotName('multi_1');
    $snapshot2 = $this->generateTestSnapshotName('multi_2');

    // Create two snapshots
    $this->artisan('weslink:snapshot:create', ['name' => $snapshot1])
        ->assertExitCode(0);

    $this->artisan('weslink:snapshot:create', ['name' => $snapshot2])
        ->assertExitCode(0);

    // Verify both exist
    expect($this->snapshotExists($snapshot1))->toBeTrue();
    expect($this->snapshotExists($snapshot2))->toBeTrue();

    // Delete first snapshot
    $this->artisan('weslink:snapshot:delete', ['name' => $snapshot1])
        ->assertExitCode(0);

    // Delete second snapshot
    $this->artisan('weslink:snapshot:delete', ['name' => $snapshot2])
        ->assertExitCode(0);

    // Verify both are deleted
    expect($this->snapshotExists($snapshot1))->toBeFalse();
    expect($this->snapshotExists($snapshot2))->toBeFalse();
});
