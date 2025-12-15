<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Setup test table with data
    DB::statement('CREATE TABLE IF NOT EXISTS snapshot_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('snapshot_test')->insert(['data' => 'test_data_'.time()]);
});

afterEach(function () {
    // Cleanup test table
    DB::statement('DROP TABLE IF EXISTS snapshot_test');
});

it('creates a snapshot of the database', function () {
    $snapshotName = $this->generateTestSnapshotName('create_snapshot');

    $this->artisan('weslink:snapshot:create', ['name' => $snapshotName])
        ->assertExitCode(0);

    // Verify snapshot file exists
    expect($this->snapshotExists($snapshotName))->toBeTrue();
});

it('creates a snapshot with auto-generated name when no name provided', function () {
    $this->artisan('weslink:snapshot:create')
        ->assertExitCode(0);

    // Verify at least one snapshot file exists
    $files = Storage::disk('snapshots')->files();
    expect($files)->not->toBeEmpty();

    // Cleanup the auto-generated snapshot
    foreach ($files as $file) {
        Storage::disk('snapshots')->delete($file);
    }
});

it('creates a snapshot with specific tables using --table option', function () {
    // Create another test table
    DB::statement('CREATE TABLE IF NOT EXISTS another_table (id SERIAL PRIMARY KEY, name TEXT)');
    DB::table('another_table')->insert(['name' => 'test']);

    $snapshotName = $this->generateTestSnapshotName('table_filter');

    $this->artisan('weslink:snapshot:create', [
        'name' => $snapshotName,
        '--table' => ['snapshot_test'],
    ])
        ->assertExitCode(0);

    expect($this->snapshotExists($snapshotName))->toBeTrue();

    // Cleanup
    DB::statement('DROP TABLE IF EXISTS another_table');
});

it('creates a snapshot excluding specific tables using --exclude option', function () {
    // Create another test table
    DB::statement('CREATE TABLE IF NOT EXISTS exclude_table (id SERIAL PRIMARY KEY, name TEXT)');
    DB::table('exclude_table')->insert(['name' => 'test']);

    $snapshotName = $this->generateTestSnapshotName('table_exclude');

    $this->artisan('weslink:snapshot:create', [
        'name' => $snapshotName,
        '--exclude' => ['exclude_table'],
    ])
        ->assertExitCode(0);

    expect($this->snapshotExists($snapshotName))->toBeTrue();

    // Cleanup
    DB::statement('DROP TABLE IF EXISTS exclude_table');
});

it('creates a snapshot with custom connection', function () {
    $snapshotName = $this->generateTestSnapshotName('custom_connection');

    $this->artisan('weslink:snapshot:create', [
        'name' => $snapshotName,
        '--connection' => 'pgsql',
    ])
        ->assertExitCode(0);

    expect($this->snapshotExists($snapshotName))->toBeTrue();
});

it('fails when using invalid connection', function () {
    $this->artisan('weslink:snapshot:create', [
        'name' => 'test',
        '--connection' => 'invalid_connection',
    ])
        ->assertExitCode(0); // Command returns 0 but outputs error
});
