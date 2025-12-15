<?php

it('creates a new database via command', function (): void {
    $dbName = $this->generateTestDatabaseName('cmd_create');

    $this->artisan('weslink:database:create', ['name' => $dbName])
        ->expectsOutput("Database with name `{$dbName}` was created!")
        ->assertExitCode(0);

    expect($this->databaseExists($dbName))->toBeTrue();
});

it('does not create database if it already exists', function (): void {
    $dbName = $this->generateTestDatabaseName('already_exists');

    // Create database first time
    $this->artisan('weslink:database:create', ['name' => $dbName])
        ->assertExitCode(0);

    expect($this->databaseExists($dbName))->toBeTrue();

    // Try to create again
    $this->artisan('weslink:database:create', ['name' => $dbName])
        ->expectsOutput('Failed to create database.')
        ->assertExitCode(0);
});

it('creates multiple databases with different names', function (): void {
    $dbName1 = $this->generateTestDatabaseName('multi_1');
    $dbName2 = $this->generateTestDatabaseName('multi_2');

    $this->artisan('weslink:database:create', ['name' => $dbName1])
        ->expectsOutput("Database with name `{$dbName1}` was created!")
        ->assertExitCode(0);

    $this->artisan('weslink:database:create', ['name' => $dbName2])
        ->expectsOutput("Database with name `{$dbName2}` was created!")
        ->assertExitCode(0);

    expect($this->databaseExists($dbName1))->toBeTrue();
    expect($this->databaseExists($dbName2))->toBeTrue();
});

it('creates database with special characters in name', function (): void {
    $dbName = $this->generateTestDatabaseName('special_chars_db');

    $this->artisan('weslink:database:create', ['name' => $dbName])
        ->expectsOutput("Database with name `{$dbName}` was created!")
        ->assertExitCode(0);

    expect($this->databaseExists($dbName))->toBeTrue();
});

it('creates database and can connect to it', function (): void {
    $dbName = $this->generateTestDatabaseName('connectable');

    $this->artisan('weslink:database:create', ['name' => $dbName])
        ->assertExitCode(0);

    expect($this->databaseExists($dbName))->toBeTrue();

    // Verify we can connect to the new database
    config()->set('database.connections.temp', array_merge(
        config('database.connections.pgsql'),
        ['database' => $dbName]
    ));

    $pdo = \Illuminate\Support\Facades\DB::connection('temp')->getPdo();
    expect($pdo)->not->toBeNull();
});
