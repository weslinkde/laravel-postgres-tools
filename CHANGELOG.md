## [1.2.0](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.1.0...v1.2.0) (2026-01-27)

### Features

* add Laravel 10 and 11 support ([f1b1b87](https://github.com/weslinkde/laravel-postgres-tools/commit/f1b1b871591de6af42c5c96ecd936e8d3dcc68c1))

## [1.1.0](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.0.2...v1.1.0) (2026-01-27)

### Features

* Add exclude-table-data option to export table structure without data ([543767d](https://github.com/weslinkde/laravel-postgres-tools/commit/543767da7241b75e6eb54188b1a3c742f555e24b))

### Build

* **deps:** Bump actions/checkout from 5 to 6 ([#19](https://github.com/weslinkde/laravel-postgres-tools/issues/19)) ([74cbbc0](https://github.com/weslinkde/laravel-postgres-tools/commit/74cbbc056a804604ff58fa9248b8de8e578bb152))
* **deps:** Bump actions/setup-node from 4 to 6 ([#18](https://github.com/weslinkde/laravel-postgres-tools/issues/18)) ([be995a0](https://github.com/weslinkde/laravel-postgres-tools/commit/be995a026d928ecb62d3a3b1ddf15c9fc2630e21))
* **deps:** Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 ([a46d578](https://github.com/weslinkde/laravel-postgres-tools/commit/a46d578730c1903c1141954dea185d54d5ccad13))

## [1.0.2](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.0.1...v1.0.2) (2025-12-15)

### CI/CD

* re-enable changelog auto-update in semantic-release ([de59526](https://github.com/weslinkde/laravel-postgres-tools/commit/de59526824bbec592625f32264bd4947840e0182))

# Changelog

All notable changes to `laravel-postgres-tools` will be documented in this file.

## v0.5.5 - 2025-10-13

### Fixed
- **PHPStan:** Fixed all remaining PHPStan errors (now passing with 0 errors)
  - Removed unnecessary `@var` PHPDoc that caused false positive errors
  - Added `@phpstan-ignore-next-line` for unavoidable interface mismatch
  - PHPStan now runs clean in CI/CD pipelines

## v0.5.4 - 2025-10-13

### Fixed
- **CI/CD:** Fixed PHPStan compatibility for PHP 8.1 environments
  - Fixed `larastan/larastan` to `^2.0` (v3.0 requires PHP 8.2+)
  - Relaxed `phpstan/phpstan-deprecation-rules` to `^1.0|^2.0`
  - Relaxed `phpstan/phpstan-phpunit` to `^1.0|^2.0`
  - Fixed `new static()` usage in exceptions (use `new self()` for PHPStan)
  - Added PHPDoc type hints for better static analysis
- **CI/CD:** Fixed test dependencies to support Laravel 10, 11, and 12
  - Relaxed `pestphp/pest` to `^2.0|^3.0`
  - Relaxed `pestphp/pest-plugin-laravel` to `^2.0|^3.0`
  - Relaxed `pestphp/pest-plugin-arch` to `^2.0|^3.0`
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
