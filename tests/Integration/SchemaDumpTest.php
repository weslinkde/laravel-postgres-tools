<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    DB::statement('CREATE TABLE IF NOT EXISTS schema_test (id SERIAL PRIMARY KEY, name VARCHAR(255), created_at TIMESTAMP)');
});

afterEach(function (): void {
    DB::statement('DROP TABLE IF EXISTS schema_test');

    // Cleanup dump files
    $disk = Storage::disk('snapshots');
    foreach ($disk->files() as $file) {
        if (str_contains((string) $file, 'schema_dump_test') || str_contains((string) $file, '_schema.sql')) {
            $disk->delete($file);
        }
    }
});

it('creates a schema dump file', function (): void {
    $name = 'schema_dump_test_'.time();

    $this->artisan('weslink:schema:dump', ['name' => $name])
        ->expectsOutputToContain('Schema dump created')
        ->assertExitCode(0);

    $disk = Storage::disk('snapshots');
    expect($disk->exists("{$name}.sql"))->toBeTrue();

    // Verify it contains schema but not data
    $content = $disk->get("{$name}.sql");
    expect($content)->toContain('CREATE TABLE');
});

it('creates schema dump with auto-generated name', function (): void {
    $this->artisan('weslink:schema:dump')
        ->expectsOutputToContain('Schema dump created')
        ->assertExitCode(0);
});

it('creates schema dump with custom connection', function (): void {
    $name = 'schema_dump_test_conn_'.time();

    $this->artisan('weslink:schema:dump', ['name' => $name, '--connection' => 'pgsql'])
        ->expectsOutputToContain('Schema dump created')
        ->assertExitCode(0);
});
