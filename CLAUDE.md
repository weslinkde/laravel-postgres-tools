# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package that provides PostgreSQL-specific database management tools. It handles large databases (16GB+) with custom snapshot/restore operations and provides utilities for creating, dropping, and cloning PostgreSQL databases.

## Development Commands

### Testing
```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run PHPStan static analysis
composer analyse
```

### Code Quality
```bash
# Fix code style issues (Laravel Pint - PSR-12)
composer format
```

### Development Environment
```bash
# Build the workbench environment
composer build

# Start the test server
composer start

# Docker-based integration tests
composer docker:up    # Start PostgreSQL container
composer docker:down  # Stop container
composer docker:test  # Run integration tests with Docker
```

## Architecture

### Core Components

**PostgresHelper** (`src/Support/PostgresHelper.php`)
- Central service class for all PostgreSQL operations
- Uses Symfony Process to execute native PostgreSQL commands (`pg_dump`, `pg_restore`, `createdb`, `dropdb`, `psql`)
- Factory method: `PostgresHelper::createForConnection(string $connectionName)` validates driver is `pgsql` and extracts connection config
- Handles database credentials via `PGPASSWORD` environment variable for secure authentication
- All Process calls use `setTimeout(0)` for large database operations

**Snapshot** (`src/Snapshot.php`)
- Core snapshot class for database dumps
- Implements streaming for non-local disks to handle large files without loading into memory
- Dispatches custom events (LoadingSnapshot, LoadedSnapshot, etc.)
- Uses Laravel Prompts for visual feedback during long-running operations

**SnapshotFactory** (`src/SnapshotFactory.php`)
- Creates snapshots using PostgresDumper
- Handles disk configuration and snapshot file naming
- Dispatches CreatingSnapshot and CreatedSnapshot events

**PostgresSnapshotRepository** (`src/PostgresSnapshotRepository.php`)
- Repository for managing snapshot files on configured disk
- Methods: `getAll()`, `findByName()`
- Bound to container using disk configuration from `config/postgres-tools.disk`

**PostgresDumper** (`src/Dumper/PostgresDumper.php`)
- PostgreSQL-specific pg_dump wrapper
- Supports table filtering (include/exclude)
- Uses PGPASSFILE for secure password handling
- Configurable extra options for pg_dump

**DbDumperFactory** (`src/DbDumperFactory.php`)
- Factory to create PostgresDumper instances from Laravel database connections
- Injects configuration from `config/postgres-tools.addExtraOption`

### Support Classes

**TemporaryDirectory** (`src/Support/TemporaryDirectory.php`)
- Manages temporary directory creation and cleanup
- Used for streaming non-local disk files

**Format** (`src/Support/Format.php`)
- Utility for human-readable file size formatting

### Events

All events are in `src/Events/`:
- `CreatingSnapshot`, `CreatedSnapshot`
- `LoadingSnapshot`, `LoadedSnapshot`
- `DeletingSnapshot`, `DeletedSnapshot`

### Commands

All commands use the `weslink:` namespace:

1. **weslink:snapshot:create** - Creates database dumps using `pg_dump` with compression (`-Z 3 -Fc`)
2. **weslink:snapshot:load** - Restores dumps using `pg_restore` with parallel jobs support
3. **weslink:snapshot:delete** - Removes snapshot files from configured disk
4. **weslink:database:create** - Creates new PostgreSQL databases via `createdb`
5. **weslink:database:drop** - Drops databases via `dropdb --force` (confirms in production)
6. **weslink:database:clone** - Clones databases by creating snapshot and restoring to new database

Commands use `AsksForSnapshotName` trait for consistent snapshot name handling.

### Configuration Flow

1. Package config published to `config/postgres-tools.php`
2. Key settings:
   - `disk`: Laravel filesystem disk for snapshot storage (default: 'snapshots')
   - `default_connection`: Database connection (default: 'pgsql')
   - `temporary_directory_path`: For streaming non-local disk files
   - `tables`/`exclude`: Filter tables in snapshots (supports env vars `PG_INCLUDE_TABLES`, `PG_EXCLUDE_TABLES`)
   - `addExtraOption`: pg_dump flags (includes `--no-owner --no-acl --no-privileges -Z 3 -Fc`)
   - `jobs`: Parallel restore jobs via `PG_RESTORE_JOBS` env var

### Service Provider Bindings

`PostgresToolsServiceProvider` extends standard Laravel `ServiceProvider`:
- Merges package config
- Registers PostgresSnapshotRepository binding
- Registers all commands
- Publishes config file

## Key Technical Details

**Large Database Optimization**
- Uses native PostgreSQL tools directly (not Laravel's database layer) for maximum performance
- Supports custom format dumps (`-Fc`) which are smaller and allow parallel restore
- Streaming implementation prevents memory exhaustion on non-local storage

**Connection Handling**
- Validates `pgsql` driver requirement in `PostgresHelper::createForConnection()`
- Handles read replicas by checking `read.host.0` and falling back to `read.host` or `host`
- Supports `connect_via_database` config for special connection scenarios

**Security**
- Uses `PGPASSFILE` environment variable for secure password handling
- Production confirmation required for destructive operations (drop database)
- SQL injection protection via Process command arrays (not shell strings)

## Testing

Uses Pest PHP for testing with Orchestra Testbench for Laravel package testing environment:

- **Unit Tests** (`tests/Unit/`) - Test individual components with mocks
- **Integration Tests** (`tests/Integration/`) - Require running PostgreSQL database
- **Architecture Tests** (`tests/ArchTest.php`) - Enforce package structure

Integration tests extend `IntegrationTestCase` which provides:
- PostgreSQL availability checks
- Test database/snapshot lifecycle management
- Docker Compose configuration for CI/CD

## Dependencies

- `symfony/process` - For executing PostgreSQL CLI tools
- `laravel/prompts` - For CLI interactions
- `illuminate/contracts`, `illuminate/filesystem`, `illuminate/support` - Laravel core
- Supports Laravel 10, 11, and 12
- PHP 8.1+ required
