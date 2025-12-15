<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Setup source database with test data
    DB::statement('CREATE TABLE IF NOT EXISTS clone_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('clone_test')->insert(['data' => 'original_data_'.time()]);
});

afterEach(function () {
    // Cleanup source database table
    DB::statement('DROP TABLE IF EXISTS clone_test');
});

it('clones a database with all its data', function () {
    $sourceDbName = config('database.connections.pgsql.database');
    $targetDbName = $this->generateTestDatabaseName('clone_target');

    $this->artisan('weslink:database:clone', [
        'databaseName' => $sourceDbName,
        'newDatabaseName' => $targetDbName,
    ])
        ->expectsOutput("Database with name `{$targetDbName}` was created!")
        ->assertExitCode(0);

    // Verify target database exists
    expect($this->databaseExists($targetDbName))->toBeTrue();

    // Verify data was cloned
    config()->set('database.connections.temp_clone', array_merge(
        config('database.connections.pgsql'),
        ['database' => $targetDbName]
    ));

    $result = DB::connection('temp_clone')->table('clone_test')->first();
    expect($result)->not->toBeNull();
    expect($result->data)->toContain('original_data');
});

it('clones a database and both databases remain independent', function () {
    $sourceDbName = config('database.connections.pgsql.database');
    $targetDbName = $this->generateTestDatabaseName('independent_clone');

    // Clone the database
    $this->artisan('weslink:database:clone', [
        'databaseName' => $sourceDbName,
        'newDatabaseName' => $targetDbName,
    ])
        ->assertExitCode(0);

    // Add data to source database
    DB::table('clone_test')->insert(['data' => 'source_only_data']);

    // Verify source has new data
    expect(DB::table('clone_test')->where('data', 'source_only_data')->exists())->toBeTrue();

    // Verify target does not have new data (independent)
    config()->set('database.connections.temp_clone', array_merge(
        config('database.connections.pgsql'),
        ['database' => $targetDbName]
    ));

    expect(DB::connection('temp_clone')->table('clone_test')->where('data', 'source_only_data')->exists())->toBeFalse();
});

it('creates temporary snapshot during clone and cleans it up', function () {
    $sourceDbName = config('database.connections.pgsql.database');
    $targetDbName = $this->generateTestDatabaseName('temp_snapshot_cleanup');

    // Get initial snapshot count
    $initialSnapshots = \Illuminate\Support\Facades\Storage::disk('snapshots')->files();

    $this->artisan('weslink:database:clone', [
        'databaseName' => $sourceDbName,
        'newDatabaseName' => $targetDbName,
    ])
        ->assertExitCode(0);

    // Verify temp snapshot was cleaned up
    $finalSnapshots = \Illuminate\Support\Facades\Storage::disk('snapshots')->files();

    // Should be same count (temp snapshot deleted)
    expect(count($finalSnapshots))->toBe(count($initialSnapshots));
});

it('clones database with multiple tables', function () {
    // Create additional tables
    DB::statement('CREATE TABLE IF NOT EXISTS clone_test_2 (id SERIAL PRIMARY KEY, name TEXT)');
    DB::table('clone_test_2')->insert(['name' => 'test_name']);

    DB::statement('CREATE TABLE IF NOT EXISTS clone_test_3 (id SERIAL PRIMARY KEY, value INT)');
    DB::table('clone_test_3')->insert(['value' => 42]);

    $sourceDbName = config('database.connections.pgsql.database');
    $targetDbName = $this->generateTestDatabaseName('multi_table_clone');

    $this->artisan('weslink:database:clone', [
        'databaseName' => $sourceDbName,
        'newDatabaseName' => $targetDbName,
    ])
        ->assertExitCode(0);

    // Verify all tables exist in cloned database
    config()->set('database.connections.temp_clone', array_merge(
        config('database.connections.pgsql'),
        ['database' => $targetDbName]
    ));

    expect(DB::connection('temp_clone')->table('clone_test')->exists())->toBeTrue();
    expect(DB::connection('temp_clone')->table('clone_test_2')->exists())->toBeTrue();
    expect(DB::connection('temp_clone')->table('clone_test_3')->exists())->toBeTrue();

    // Cleanup additional tables
    DB::statement('DROP TABLE IF EXISTS clone_test_2');
    DB::statement('DROP TABLE IF EXISTS clone_test_3');
});

it('clones empty database', function () {
    // Create an empty database to clone
    $emptySourceDb = $this->generateTestDatabaseName('empty_source');
    $this->createTestDatabase($emptySourceDb);

    $targetDbName = $this->generateTestDatabaseName('empty_clone_target');

    $this->artisan('weslink:database:clone', [
        'databaseName' => $emptySourceDb,
        'newDatabaseName' => $targetDbName,
    ])
        ->expectsOutput("Database with name `{$targetDbName}` was created!")
        ->assertExitCode(0);

    expect($this->databaseExists($targetDbName))->toBeTrue();
});
