## [2.0.0](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.4.0...v2.0.0) (2026-08-28)

### ⚠ BREAKING CHANGES

* commands now exit non-zero when they fail. "Database already
exists", "database does not exist", "no snapshots found", "snapshot does not
exist" and a declined confirmation return exit code 1 instead of 0, and the
create/drop failure messages changed.

Claude-Session: https://claude.ai/code/session_01TDxz7jfZj2cQyPV1K7a477

* fix!: harden every command against silent failure

Follow-up to the restore fix, covering the same defect class across the rest of
the package.

- PostgresDumper built its pg_dump invocation as a shell string and ran it
  through `Process::fromShellCommandline`, with the host and every table name
  interpolated unquoted. `weslink:snapshot:create --table="x; …"` and
  `PG_INCLUDE_TABLES` could therefore execute arbitrary commands. The command is
  now an argument array executed without a shell.
- pg_dump writes with `--file` instead of a `>` redirect. A seekable target lets
  pg_dump record the data offsets in the archive, which is what
  `pg_restore --jobs` needs to restore in parallel.
- streamToLocalFile() checks every write and compares the streamed byte count
  against the size the disk reports. A download that broke off used to produce a
  truncated dump that passed the preflight and failed after the tables were
  gone.
- The remaining eight commands returned void and exited 0 after printing their
  error. They return int now, with self::FAILURE on every path that did not do
  what was asked. An empty listing stays a success.
- CloneDatabase used the fixed snapshot name `temp-snapshot` and deleted it
  afterwards, so concurrent clones fought over one file and a real snapshot of
  that name was destroyed. The name is unique per run now.
- Removed the copy-pasted, unreachable askForSnapshotName() from CreateDatabase
  and DropDatabase.
- Replaced the placeholder skipped test in ListDatabasesTest with one that
  asserts an unreachable server fails instead of reporting an empty list.
- README and CLAUDE.md claimed PHP 8.1 and Laravel 10-12; composer.json requires
  PHP ^8.2 and Laravel 11-13.
* getDumpCommand() returns an array instead of a string, and the
protected echoToFile(), determineQuote() and isWindows() helpers are gone. All
commands now return an exit code: snapshot deletion of a missing snapshot,
dumping with an invalid connection and a failed schema/data dump or VACUUM
ANALYZE return 1 instead of 0.

Claude-Session: https://claude.ai/code/session_01TDxz7jfZj2cQyPV1K7a477

### Bug Fixes

* fail loudly on failed restores instead of silently emptying the database ([#27](https://github.com/weslinkde/laravel-postgres-tools/issues/27)) ([9eef7e9](https://github.com/weslinkde/laravel-postgres-tools/commit/9eef7e9f8a4e9861d9425d60c9c218c45dfeb89f))

### Build

* **deps:** Bump actions/checkout from 6 to 7 ([#25](https://github.com/weslinkde/laravel-postgres-tools/issues/25)) ([ba5aa68](https://github.com/weslinkde/laravel-postgres-tools/commit/ba5aa68fb5fba263c935346052372791a4708154))
* **deps:** Bump actions/setup-node from 6 to 7 ([#26](https://github.com/weslinkde/laravel-postgres-tools/issues/26)) ([726d2f1](https://github.com/weslinkde/laravel-postgres-tools/commit/726d2f1456149e64af5810476bc7cfa75fe76e41))
* **deps:** Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 ([7040d93](https://github.com/weslinkde/laravel-postgres-tools/commit/7040d933a303275fad6914f36586cc95a44c5269))

## [1.4.0](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.3.1...v1.4.0) (2026-03-31)

### Features

* add Laravel 13 support, drop Laravel 10 ([#23](https://github.com/weslinkde/laravel-postgres-tools/issues/23)) ([117c9ae](https://github.com/weslinkde/laravel-postgres-tools/commit/117c9aec5769adf0b8ccb8f377cd8002795c1ca1))

### Build

* **deps:** Bump dependabot/fetch-metadata from 2.5.0 to 3.0.0 ([#22](https://github.com/weslinkde/laravel-postgres-tools/issues/22)) ([fc24adf](https://github.com/weslinkde/laravel-postgres-tools/commit/fc24adf2950761ab726c8daf865fb0de760c9e1e))
* **deps:** Bump ramsey/composer-install from 3 to 4 ([#21](https://github.com/weslinkde/laravel-postgres-tools/issues/21)) ([e6f0475](https://github.com/weslinkde/laravel-postgres-tools/commit/e6f04755c37b38ce1293f2b4973eb93515db6a4c))

## [1.3.1](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.3.0...v1.3.1) (2026-03-12)

### Bug Fixes

* pass --database override to PostgresDumper in snapshot:create ([0383d04](https://github.com/weslinkde/laravel-postgres-tools/commit/0383d04af3e317040e97c5a300e71aa4f8d05701))

## [1.3.0](https://github.com/weslinkde/laravel-postgres-tools/compare/v1.2.0...v1.3.0) (2026-03-11)

### Features

* auto-create database when loading snapshot with --database option ([e47fbde](https://github.com/weslinkde/laravel-postgres-tools/commit/e47fbde6af66a2d6db486844ad3208ba8aca0d6f))
* trigger release for auto-create database on snapshot load ([b373899](https://github.com/weslinkde/laravel-postgres-tools/commit/b37389930c63c3b97dbe682b3854eb520d68975d))

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
