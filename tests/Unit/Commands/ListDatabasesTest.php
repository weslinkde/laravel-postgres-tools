<?php

it('displays error for invalid connection', function (): void {
    $this->app['config']->set('database.connections.invalid', null);

    $this->artisan('weslink:database:list', ['--connection' => 'invalid'])
        ->assertExitCode(1);
});

it('fails instead of reporting an empty list when the server is unreachable', function (): void {
    $this->app['config']->set('database.connections.unreachable', [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        // Nothing listens on port 1, so psql cannot connect.
        'port' => 1,
        'database' => 'irrelevant',
        'username' => 'irrelevant',
        'password' => 'irrelevant',
    ]);

    $this->artisan('weslink:database:list', ['--connection' => 'unreachable'])
        ->doesntExpectOutput('No databases found.')
        ->assertExitCode(1);
});
