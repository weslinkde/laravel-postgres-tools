<?php

use Weslinkde\PostgresTools\Exceptions\RestoreFailed;
use Weslinkde\PostgresTools\Support\PostgresHelper;

beforeEach(function (): void {
    config()->set('database.connections.pgsql', [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'irrelevant',
        'username' => 'irrelevant',
        'password' => 'irrelevant',
    ]);

    $this->tempDirectory = sys_get_temp_dir().'/postgres-tools-'.uniqid();
    mkdir($this->tempDirectory);
});

afterEach(function (): void {
    foreach (glob($this->tempDirectory.'/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($this->tempDirectory);
});

/**
 * Build a pg_dump custom format archive header.
 */
function archiveHeader(int $major, int $minor, int $revision = 0): string
{
    return 'PGDMP'
        .chr($major).chr($minor).chr($revision)
        .chr(4)  // intSize
        .chr(8)  // offSize
        .chr(1); // format: custom
}

it('reads the archive version from a pg_dump archive header', function (): void {
    $filePath = $this->tempDirectory.'/snapshot.sql';
    file_put_contents($filePath, archiveHeader(1, 15).str_repeat("\0", 128));

    expect(PostgresHelper::readArchiveVersion($filePath))->toBe('1.15');
});

it('returns null when the file is not a pg_dump archive', function (): void {
    $filePath = $this->tempDirectory.'/plain.sql';
    file_put_contents($filePath, "-- plain SQL dump\nCREATE TABLE foo (id int);\n");

    expect(PostgresHelper::readArchiveVersion($filePath))->toBeNull();
});

it('returns null when the file does not exist', function (): void {
    expect(PostgresHelper::readArchiveVersion($this->tempDirectory.'/missing.sql'))->toBeNull();
});

it('returns null for a file that is shorter than the archive header', function (): void {
    $filePath = $this->tempDirectory.'/truncated.sql';
    file_put_contents($filePath, 'PGDMP');

    expect(PostgresHelper::readArchiveVersion($filePath))->toBeNull();
});

it('resolves client binaries from PATH when no bin path is configured', function (): void {
    config()->set('postgres-tools.bin_path', '');

    expect(PostgresHelper::createForConnection('pgsql')->binary('pg_restore'))->toBe('pg_restore');
});

it('resolves client binaries from the configured bin path', function (): void {
    config()->set('postgres-tools.bin_path', '/opt/homebrew/opt/postgresql@16/bin/');

    expect(PostgresHelper::createForConnection('pgsql')->binary('pg_restore'))
        ->toBe('/opt/homebrew/opt/postgresql@16/bin/pg_restore');
});

it('rejects a snapshot file that does not exist', function (): void {
    PostgresHelper::createForConnection('pgsql')
        ->assertSnapshotIsRestorable($this->tempDirectory.'/missing.sql');
})->throws(RestoreFailed::class, 'does not exist or is not readable');

it('rejects an archive the local client cannot read', function (): void {
    $filePath = $this->tempDirectory.'/unsupported.sql';

    // No pg_restore supports archive format 1.99.
    file_put_contents($filePath, archiveHeader(1, 99).str_repeat("\0", 512));

    PostgresHelper::createForConnection('pgsql')->assertSnapshotIsRestorable($filePath);
})->throws(RestoreFailed::class, 'archive format version 1.99');

it('rejects a file that is not a pg_dump archive at all', function (): void {
    $filePath = $this->tempDirectory.'/plain.sql';

    // Snapshots are always written in custom format; pg_restore cannot read plain SQL.
    file_put_contents($filePath, "CREATE TABLE foo (id int);\n");

    PostgresHelper::createForConnection('pgsql')->assertSnapshotIsRestorable($filePath);
})->throws(RestoreFailed::class, 'the file is not a pg_dump archive');

it('rejects a truncated archive', function (): void {
    $filePath = $this->tempDirectory.'/truncated.sql';
    file_put_contents($filePath, '');

    PostgresHelper::createForConnection('pgsql')->assertSnapshotIsRestorable($filePath);
})->throws(RestoreFailed::class);
