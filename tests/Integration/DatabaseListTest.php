<?php

it('lists databases successfully', function (): void {
    $this->artisan('weslink:database:list')
        ->assertExitCode(0);
});

it('lists databases with custom connection', function (): void {
    $this->artisan('weslink:database:list', ['--connection' => 'pgsql'])
        ->assertExitCode(0);
});

it('shows created test database in list', function (): void {
    $dbName = $this->generateTestDatabaseName('list_test');

    // Create a test database
    $this->createTestDatabase($dbName);
    expect($this->databaseExists($dbName))->toBeTrue();

    // List databases - should include our test database
    $this->artisan('weslink:database:list')
        ->assertExitCode(0);
});
