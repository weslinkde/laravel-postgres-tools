<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\PostgresHelper;

it('creates a new database', function () {
    $dbName = $this->generateTestDatabaseName('create');
    $helper = PostgresHelper::createForConnection('pgsql')
        ->setName($dbName);

    $process = $helper->createDatabase();

    expect($process)->not->toBeFalse()
        ->and($helper->checkIfDatabaseExists($dbName))->toBeTrue();
});

it('returns false when creating an already existing database', function () {
    $dbName = $this->generateTestDatabaseName('existing');
    $helper = PostgresHelper::createForConnection('pgsql')
        ->setName($dbName);

    // Create database first time
    $process1 = $helper->createDatabase();
    expect($process1)->not->toBeFalse();

    // Try to create same database again
    $process2 = $helper->createDatabase();
    expect($process2)->toBeFalse();
});

it('drops an existing database', function () {
    $dbName = $this->generateTestDatabaseName('drop');
    $helper = PostgresHelper::createForConnection('pgsql')
        ->setName($dbName);

    // Create database first
    $helper->createDatabase();
    expect($helper->checkIfDatabaseExists($dbName))->toBeTrue();

    // Remove from tracking so afterEach doesn't try to drop it again
    $this->testDatabases = array_filter($this->testDatabases, fn ($name) => $name !== $dbName);

    // Drop the database
    $process = $helper->dropDatabase();

    expect($process)->not->toBeFalse()
        ->and($helper->checkIfDatabaseExists($dbName))->toBeFalse();
});

it('returns false when dropping a non-existent database', function () {
    $dbName = $this->generateTestDatabaseName('nonexistent');
    $helper = PostgresHelper::createForConnection('pgsql')
        ->setName($dbName);

    // Remove from tracking as we won't create it
    $this->testDatabases = array_filter($this->testDatabases, fn ($name) => $name !== $dbName);

    $process = $helper->dropDatabase();

    expect($process)->toBeFalse();
});

it('checks if database exists', function () {
    $dbName = $this->generateTestDatabaseName('check');
    $helper = PostgresHelper::createForConnection('pgsql');

    // Database should not exist initially
    expect($helper->checkIfDatabaseExists($dbName))->toBeFalse();

    // Create the database
    $helper->setName($dbName)->createDatabase();

    // Now it should exist
    expect($helper->checkIfDatabaseExists($dbName))->toBeTrue();
});

it('throws exception for non-pgsql driver', function () {
    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    PostgresHelper::createForConnection('sqlite');
})->throws(CannotCreateConnection::class, 'Driver `sqlite` is not supported');

it('throws exception for non-existent connection', function () {
    PostgresHelper::createForConnection('nonexistent');
})->throws(CannotCreateConnection::class, 'Connection `nonexistent` does not exist');

it('creates helper with valid pgsql connection', function () {
    $helper = PostgresHelper::createForConnection('pgsql');

    expect($helper)->toBeInstanceOf(PostgresHelper::class);
});

it('creates a snapshot using pg_dump', function () {
    // Create a test table with data
    DB::statement('CREATE TABLE IF NOT EXISTS pg_dump_test (id SERIAL PRIMARY KEY, name VARCHAR(255))');
    DB::table('pg_dump_test')->insert(['name' => 'test_entry']);

    $helper = PostgresHelper::createForConnection('pgsql');
    $snapshotName = 'dump_test_'.time();

    // Create snapshot
    $snapshot = $helper->createSnapshot($snapshotName);

    expect($snapshot)->not->toBeNull()
        ->and(Storage::disk('snapshots')->exists($snapshot->fileName))->toBeTrue();

    // Cleanup
    Storage::disk('snapshots')->delete($snapshot->fileName);
    DB::statement('DROP TABLE IF EXISTS pg_dump_test');
});

it('creates snapshot with table filtering', function () {
    // Create two test tables
    DB::statement('CREATE TABLE IF NOT EXISTS include_table (id SERIAL PRIMARY KEY, name VARCHAR(255))');
    DB::statement('CREATE TABLE IF NOT EXISTS exclude_table (id SERIAL PRIMARY KEY, name VARCHAR(255))');
    DB::table('include_table')->insert(['name' => 'included']);
    DB::table('exclude_table')->insert(['name' => 'excluded']);

    $helper = PostgresHelper::createForConnection('pgsql');
    $snapshotName = 'filter_test_'.time();

    // Create snapshot with only include_table
    $snapshot = $helper->createSnapshot($snapshotName, ['include_table'], null);

    expect($snapshot)->not->toBeNull()
        ->and(Storage::disk('snapshots')->exists($snapshot->fileName))->toBeTrue();

    // Cleanup
    Storage::disk('snapshots')->delete($snapshot->fileName);
    DB::statement('DROP TABLE IF EXISTS include_table');
    DB::statement('DROP TABLE IF EXISTS exclude_table');
});

it('restores a snapshot using pg_restore', function () {
    // Setup: Create table and data
    DB::statement('CREATE TABLE IF NOT EXISTS pg_restore_test (id SERIAL PRIMARY KEY, name VARCHAR(255))');
    DB::table('pg_restore_test')->insert(['name' => 'restore_test_entry']);

    $helper = PostgresHelper::createForConnection('pgsql');
    $snapshotName = 'restore_test_'.time();

    // Create snapshot
    $snapshot = $helper->createSnapshot($snapshotName);
    expect(Storage::disk('snapshots')->exists($snapshot->fileName))->toBeTrue();

    // Get the local file path for restore
    $filePath = Storage::disk('snapshots')->path($snapshot->fileName);

    // Delete the data
    DB::table('pg_restore_test')->truncate();
    expect(DB::table('pg_restore_test')->count())->toBe(0);

    // Restore using pg_restore
    $process = $helper->restoreSnapshot($filePath);

    expect($process->isSuccessful())->toBeTrue()
        ->and(DB::table('pg_restore_test')->where('name', 'restore_test_entry')->exists())->toBeTrue();

    // Cleanup
    Storage::disk('snapshots')->delete($snapshot->fileName);
    DB::statement('DROP TABLE IF EXISTS pg_restore_test');
});

it('can set connection properties via setters', function () {
    $helper = PostgresHelper::createForConnection('pgsql')
        ->setHost('custom-host')
        ->setPort(5433)
        ->setUserName('custom-user')
        ->setPassword('custom-pass')
        ->setName('custom-db');

    expect($helper)->toBeInstanceOf(PostgresHelper::class);
});

it('handles read replica configuration', function () {
    config()->set('database.connections.pgsql_with_replica', [
        'driver' => 'pgsql',
        'read' => [
            'host' => ['replica1.example.com', 'replica2.example.com'],
        ],
        'write' => [
            'host' => ['primary.example.com'],
        ],
        'host' => 'fallback.example.com',
        'port' => 5432,
        'database' => 'test_db',
        'username' => 'test_user',
        'password' => 'test_pass',
    ]);

    $helper = PostgresHelper::createForConnection('pgsql_with_replica');

    expect($helper)->toBeInstanceOf(PostgresHelper::class);
});

it('handles connect_via_database configuration', function () {
    config()->set('database.connections.pgsql_via', [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'actual_db',
        'connect_via_database' => 'postgres',
        'username' => 'test_user',
        'password' => 'test_pass',
    ]);

    $helper = PostgresHelper::createForConnection('pgsql_via');

    expect($helper)->toBeInstanceOf(PostgresHelper::class);
});
