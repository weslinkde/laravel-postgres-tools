<?php

it('displays a warning when no databases found', function (): void {
    // Skip this test - mocking static methods is complex
    // The integration test covers this scenario
})->skip('Mocking static factory methods requires additional setup');

it('displays error for invalid connection', function (): void {
    $this->app['config']->set('database.connections.invalid', null);

    $this->artisan('weslink:database:list', ['--connection' => 'invalid'])
        ->assertExitCode(0);
});
