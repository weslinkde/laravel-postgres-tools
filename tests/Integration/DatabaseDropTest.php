<?php

it('drops an existing database via command', function (): void {
    $dbName = $this->generateTestDatabaseName('drop_test');

    // Create database first
    $this->createTestDatabase($dbName);
    expect($this->databaseExists($dbName))->toBeTrue();

    // Drop the database (ConfirmableTrait passes automatically in testing)
    $this->artisan('weslink:database:drop', ['name' => $dbName])
        ->expectsOutput("Database with name `{$dbName}` was dropped!")
        ->assertExitCode(0);

    expect($this->databaseExists($dbName))->toBeFalse();
});

it('does not drop database if it does not exist', function (): void {
    $dbName = 'non_existent_db_'.time();

    $this->artisan('weslink:database:drop', ['name' => $dbName])
        ->expectsOutput('Failed to drop database.')
        ->assertExitCode(0);
});

it('drops multiple databases sequentially', function (): void {
    $dbName1 = $this->generateTestDatabaseName('multi_drop_1');
    $dbName2 = $this->generateTestDatabaseName('multi_drop_2');

    // Create both databases
    $this->createTestDatabase($dbName1);
    $this->createTestDatabase($dbName2);

    expect($this->databaseExists($dbName1))->toBeTrue();
    expect($this->databaseExists($dbName2))->toBeTrue();

    // Drop first database
    $this->artisan('weslink:database:drop', ['name' => $dbName1])
        ->expectsOutput("Database with name `{$dbName1}` was dropped!")
        ->assertExitCode(0);

    // Drop second database
    $this->artisan('weslink:database:drop', ['name' => $dbName2])
        ->expectsOutput("Database with name `{$dbName2}` was dropped!")
        ->assertExitCode(0);

    expect($this->databaseExists($dbName1))->toBeFalse();
    expect($this->databaseExists($dbName2))->toBeFalse();
});
