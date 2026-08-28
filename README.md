# Laravel Postgres Tools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/weslinkde/laravel-postgres-tools.svg?style=flat-square)](https://packagist.org/packages/weslinkde/laravel-postgres-tools)
[![GitHub Tests Action Status](https://github.com/weslinkde/laravel-postgres-tools/actions/workflows/run-tests.yml/badge.svg?branch=master)](https://github.com/weslinkde/laravel-postgres-tools/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://github.com/weslinkde/laravel-postgres-tools/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=master)](https://github.com/weslinkde/laravel-postgres-tools/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/weslinkde/laravel-postgres-tools.svg?style=flat-square)](https://packagist.org/packages/weslinkde/laravel-postgres-tools)

A Laravel package for PostgreSQL database management, optimized for large databases (16GB+). Create snapshots, restore backups, and manage databases with native PostgreSQL tools for maximum performance.

## Features

- **Database Snapshots**: Create and restore database dumps using native `pg_dump` and `pg_restore`
- **Large Database Support**: Optimized for databases 16GB+ with streaming and parallel processing
- **Database Management**: Create, drop, and clone PostgreSQL databases
- **Flexible Storage**: Store snapshots on any Laravel filesystem disk (local, S3, etc.)
- **Table Filtering**: Include or exclude specific tables from snapshots
- **Parallel Restore**: Configure parallel jobs for faster restoration
- **Fails Loudly**: Every PostgreSQL command is checked; a failed restore aborts with the original `pg_restore` error and a non-zero exit code

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- PostgreSQL database
- PostgreSQL CLI tools (`pg_dump`, `pg_restore`, `createdb`, `dropdb`), at least as new as the server the snapshots come from

## Installation

Install the package via composer:

```bash
composer require weslinkde/laravel-postgres-tools
```

Publish the config file:

```bash
php artisan vendor:publish --tag="postgres-tools-config"
```

### Configuration

```php
return [
    // Laravel filesystem disk for storing snapshots
    'disk' => 'snapshots',

    // Default database connection (must be pgsql driver)
    'default_connection' => 'pgsql',

    // Temporary directory for streaming from remote disks
    'temporary_directory_path' => storage_path('app/laravel-db-snapshots/temp'),

    // Directory containing the PostgreSQL client binaries (empty = resolve from PATH)
    'bin_path' => env('PG_BIN_PATH', ''),

    // Include only these tables (null = all tables)
    'tables' => env('PG_INCLUDE_TABLES', null),

    // Exclude these tables (null = no exclusions)
    'exclude' => env('PG_EXCLUDE_TABLES', null),

    // pg_dump options
    'addExtraOption' => env('PG_DUMP_OPTIONS', '--no-owner --no-acl --no-privileges -Z 3 -Fc'),

    // Parallel restore jobs
    'jobs' => env('PG_RESTORE_JOBS', 4),
];
```

Don't forget to configure your snapshots disk in `config/filesystems.php`:

```php
'disks' => [
    'snapshots' => [
        'driver' => 'local',
        'root' => storage_path('app/snapshots'),
    ],
],
```

## Usage

### Create a Snapshot

```bash
# With a custom name
php artisan weslink:snapshot:create my-backup

# Auto-generated name (timestamp)
php artisan weslink:snapshot:create

# Include only specific tables
php artisan weslink:snapshot:create --table=users --table=posts

# Exclude specific tables
php artisan weslink:snapshot:create --exclude=logs --exclude=cache

# Use a different connection
php artisan weslink:snapshot:create --connection=other_pgsql
```

### Load a Snapshot

```bash
# Load a specific snapshot
php artisan weslink:snapshot:load my-backup

# Load to a different connection
php artisan weslink:snapshot:load my-backup --connection=other_pgsql

# Load the most recent snapshot
php artisan weslink:snapshot:load --latest

# Skip dropping existing tables
php artisan weslink:snapshot:load my-backup --drop-tables=0

# Skip confirmation prompt
php artisan weslink:snapshot:load my-backup --force
```

### Delete a Snapshot

```bash
php artisan weslink:snapshot:delete my-backup
```

### Database Management

```bash
# Create a new database
php artisan weslink:database:create new_database

# Drop a database (requires confirmation in production)
php artisan weslink:database:drop old_database

# Clone a database
php artisan weslink:database:clone source_db target_db
```

## Performance Tuning

### Compression Level

The `-Z` flag controls compression (0-9). Higher = smaller files but slower:

| Level | Speed | Use Case |
|-------|-------|----------|
| `-Z 1` | Fastest | Very large databases (50GB+) |
| `-Z 3` | Balanced | Recommended default |
| `-Z 9` | Slowest | Maximum compression needed |

```bash
# In your .env file
PG_DUMP_OPTIONS="--no-owner --no-acl --no-privileges -Z 1 -Fc"
```

### Parallel Restore

Snapshots are written with `pg_dump --file`, so the archive records data offsets and `pg_restore --jobs` can genuinely restore in parallel. Configure parallel jobs based on database size:

| Database Size | Recommended Jobs |
|--------------|------------------|
| < 1GB | 1-2 |
| 1-10GB | 4 |
| 10GB+ | CPU cores - 2 |

```bash
# In your .env file
PG_RESTORE_JOBS=8
```

### Cloud Storage

When using remote storage (S3, etc.), snapshots are automatically streamed to a local temp directory during restore to avoid memory issues.

## Client Version Mismatches

`pg_restore` can only read archives written by a `pg_dump` of the same major version or older. A PostgreSQL 14 client reading a dump from a PostgreSQL 16 server fails with:

```
pg_restore: error: unsupported version (1.15) in file header
```

`weslink:snapshot:load` verifies the archive with `pg_restore --list` **before** it drops a single table, so a mismatch aborts while the target database is still intact:

```
The local PostgreSQL client cannot read the snapshot archive `/path/to/snapshot.sql`
(archive format version 1.15, client: pg_restore (PostgreSQL) 14.24). The database was
left untouched. Install a PostgreSQL client that is at least as new as the server the
snapshot was dumped from, or point `postgres-tools.bin_path` at one.
```

Instead of relying on whatever `pg_restore` happens to be first on `PATH`, pin the client explicitly:

```bash
# In your .env file
PG_BIN_PATH=/opt/homebrew/opt/postgresql@16/bin
```

This path is used for `pg_dump`, `pg_restore`, `psql`, `createdb` and `dropdb` alike.

## Error Handling

Every command exits with a non-zero status when it fails, so scripts and CI can detect problems:

| Situation | Behaviour |
|-----------|-----------|
| Archive unreadable by the local client | Aborts before dropping anything, exit code 1 |
| `pg_restore` fails during the restore | `RestoreFailed` with the full `pg_restore` stderr, exit code 1 |
| `createdb` / `dropdb` fails | `DatabaseOperationFailed` with the client stderr, exit code 1 |
| `psql` cannot reach the server | Throws instead of reporting "database does not exist" |
| Snapshot streamed incompletely from a remote disk | Aborts before dropping anything, exit code 1 |
| Snapshot name not found | Warning, exit code 1 |
| Schema/data dump or `VACUUM ANALYZE` fails | Error with the client stderr, exit code 1 |
| Listing an empty set of snapshots or databases | Warning, exit code 0 — an empty result is not a failure |

The exceptions live in `Weslinkde\PostgresTools\Exceptions` and all extend `ProcessFailed`, which carries the exit code, stdout and stderr of the failed command in its message.

## Security

All PostgreSQL commands are built as argument arrays and executed without a shell, so table names, hosts and database names coming from configuration or command line options cannot be interpreted as shell syntax. Database names are handed to `psql` as variables (`:'dbname'`) rather than interpolated into SQL. Passwords are passed via `PGPASSWORD` / `PGPASSFILE`, never on the command line.

## Events

The package dispatches events during snapshot operations:

| Event | Description |
|-------|-------------|
| `Weslinkde\PostgresTools\Events\CreatingSnapshot` | Before snapshot creation |
| `Weslinkde\PostgresTools\Events\CreatedSnapshot` | After snapshot creation |
| `Weslinkde\PostgresTools\Events\LoadingSnapshot` | Before snapshot loading |
| `Weslinkde\PostgresTools\Events\LoadedSnapshot` | After snapshot loading |
| `Weslinkde\PostgresTools\Events\DeletingSnapshot` | Before snapshot deletion |
| `Weslinkde\PostgresTools\Events\DeletedSnapshot` | After snapshot deletion |

### Example Event Listener

```php
use Weslinkde\PostgresTools\Events\CreatedSnapshot;

class NotifyBackupComplete
{
    public function handle(CreatedSnapshot $event): void
    {
        // $event->snapshot contains the Snapshot instance
        Log::info("Snapshot created: {$event->snapshot->name}");
    }
}
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run PHPStan analysis
composer analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dominik Lenz](https://github.com/Udaberrico)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
