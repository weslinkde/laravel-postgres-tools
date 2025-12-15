<?php

use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    DB::statement('CREATE TABLE IF NOT EXISTS size_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('size_test')->insert(['data' => 'test_data']);
});

afterEach(function (): void {
    DB::statement('DROP TABLE IF EXISTS size_test');
});

it('shows database size information', function (): void {
    $this->artisan('weslink:database:size')
        ->assertExitCode(0);
});

it('shows database size with custom connection', function (): void {
    $this->artisan('weslink:database:size', ['--connection' => 'pgsql'])
        ->assertExitCode(0);
});
