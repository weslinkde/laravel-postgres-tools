# Changelog

All notable changes to `laravel-postgres-tools` will be documented in this file.

## v0.5.4 - 2025-10-13

### Fixed
- **CI/CD:** Fixed test dependencies to support Laravel 10, 11, and 12
  - Relaxed `pestphp/pest` to `^2.0|^3.0`
  - Relaxed `pestphp/pest-plugin-laravel` to `^2.0|^3.0`
  - Relaxed `pestphp/pest-plugin-arch` to `^2.0|^3.0`
  - Relaxed `larastan/larastan` to `^2.0|^3.0`
  - Relaxed `orchestra/testbench` to `^8.0|^9.0|^10.0`
- **CI/CD:** Updated GitHub Actions test matrix to test Laravel 10.*, 11.*, and 12.*
- This resolves "Your requirements could not be resolved" errors in CI/CD pipelines

## v0.5.3 - 2025-10-13

### Fixed
- **CI/CD:** Fixed `nunomaduro/collision` requirement to `^7.0|^8.0` for PHP 8.1 compatibility in GitHub Actions
- This resolves CI failures with PHP 8.1 environments

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
