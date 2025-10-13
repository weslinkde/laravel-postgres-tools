# Changelog

All notable changes to `laravel-postgres-tools` will be documented in this file.

## v0.5.2 - 2025-10-13

### Changed
- **PERFORMANCE:** Improved default compression from `-Z 9` to `-Z 3` (3-5x faster with minimal size difference)
- **PERFORMANCE:** Increased default restore jobs from 1 to 4 for faster restoration of large databases
- Added environment variable support: `PG_DUMP_OPTIONS` and `PG_RESTORE_JOBS` for easy customization
- Added comprehensive Performance Tuning section to README with recommendations for different database sizes
- Improved `composer.json` syntax for `illuminate/contracts` requirement

## v0.5.1 - 2025-10-13

### Fixed
- **CRITICAL:** Relaxed `laravel/prompts` requirement to `^0.1.15|^0.2|^0.3` for better compatibility with existing Laravel installations
- This fixes composer dependency conflicts when updating the package

## v0.5.0 - 2025-10-13

### Fixed
- **CRITICAL:** Fixed return type compatibility in `PostgresSnapshotRepository::findByName()` to match parent class signature from Spatie package. This fixes fatal errors in Laravel 11.45+ installations.
- Fixed namespace issue in `PostgresSnapshot` class
- Added proper `use Spatie\DbSnapshots\Snapshot` import

### Added
- Comprehensive test suite for `LoadSnapshot` command using Pest PHP

### Changed
- Updated development dependencies (Pest, PHPStan, GitHub Actions)
- Improved Laravel 11 compatibility

## v0.4.0 - Previous Release

Initial stable release with PostgreSQL snapshot functionality.
