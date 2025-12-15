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

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- PostgreSQL database
- PostgreSQL CLI tools (`pg_dump`, `pg_restore`, `createdb`, `dropdb`)

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

Configure parallel jobs based on database size:

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
