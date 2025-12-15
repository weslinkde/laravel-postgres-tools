<?php

use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    DB::statement('CREATE TABLE IF NOT EXISTS vacuum_test (id SERIAL PRIMARY KEY, data TEXT)');
    DB::table('vacuum_test')->insert(['data' => 'test_data']);
});

afterEach(function (): void {
    DB::statement('DROP TABLE IF EXISTS vacuum_test');
});

it('runs vacuum analyze on all tables', function (): void {
    $this->artisan('weslink:database:vacuum')
        ->expectsOutputToContain('VACUUM ANALYZE completed')
        ->assertExitCode(0);
});

it('runs vacuum analyze on specific table', function (): void {
    $this->artisan('weslink:database:vacuum', ['--table' => ['vacuum_test']])
        ->expectsOutputToContain('VACUUM ANALYZE completed')
        ->assertExitCode(0);
});

it('runs vacuum analyze with custom connection', function (): void {
    $this->artisan('weslink:database:vacuum', ['--connection' => 'pgsql'])
        ->expectsOutputToContain('VACUUM ANALYZE completed')
        ->assertExitCode(0);
});
