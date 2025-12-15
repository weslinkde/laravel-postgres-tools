<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    DB::statement('CREATE TABLE IF NOT EXISTS data_dump_test (id SERIAL PRIMARY KEY, name VARCHAR(255))');
    DB::table('data_dump_test')->insert([
        ['name' => 'Test User 1'],
        ['name' => 'Test User 2'],
    ]);
});

afterEach(function (): void {
    DB::statement('DROP TABLE IF EXISTS data_dump_test');

    // Cleanup dump files
    $disk = Storage::disk('snapshots');
    foreach ($disk->files() as $file) {
        if (str_contains((string) $file, 'data_dump_test') || str_contains((string) $file, '_data.sql')) {
            $disk->delete($file);
        }
    }
});

it('creates a data dump file', function (): void {
    $name = 'data_dump_test_'.time();

    $this->artisan('weslink:data:dump', ['name' => $name])
        ->expectsOutputToContain('Data dump created')
        ->assertExitCode(0);

    $disk = Storage::disk('snapshots');
    expect($disk->exists("{$name}.sql"))->toBeTrue();

    // Verify it contains data (COPY or INSERT statements)
    $content = $disk->get("{$name}.sql");
    expect($content)->toMatch('/COPY|INSERT/');
});

it('creates data dump for specific tables', function (): void {
    $name = 'data_dump_test_table_'.time();

    $this->artisan('weslink:data:dump', ['name' => $name, '--table' => ['data_dump_test']])
        ->expectsOutputToContain('Data dump created')
        ->expectsOutputToContain('data_dump_test')
        ->assertExitCode(0);

    $disk = Storage::disk('snapshots');
    expect($disk->exists("{$name}.sql"))->toBeTrue();
});

it('creates data dump with auto-generated name', function (): void {
    $this->artisan('weslink:data:dump')
        ->expectsOutputToContain('Data dump created')
        ->assertExitCode(0);
});

it('creates data dump with custom connection', function (): void {
    $name = 'data_dump_test_conn_'.time();

    $this->artisan('weslink:data:dump', ['name' => $name, '--connection' => 'pgsql'])
        ->expectsOutputToContain('Data dump created')
        ->assertExitCode(0);
});
