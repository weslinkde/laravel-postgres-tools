# This is my package laravel-postgres-tools

[![Latest Version on Packagist](https://img.shields.io/packagist/v/weslinkde/laravel-postgres-tools.svg?style=flat-square)](https://packagist.org/packages/weslinkde/laravel-postgres-tools)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/weslinkde/laravel-postgres-tools/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/weslinkde/laravel-postgres-tools/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/weslinkde/laravel-postgres-tools/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/weslinkde/laravel-postgres-tools/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/weslinkde/laravel-postgres-tools.svg?style=flat-square)](https://packagist.org/packages/weslinkde/laravel-postgres-tools)

This package provides some tools to make working with postgres easier.

## Installation

You can install the package via composer:

```bash
composer require weslinkde/laravel-postgres-tools
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-postgres-tools-config"
```

This is the contents of the published config file:

```php
return [
    /*
     * The name of the disk on which the snapshots are stored.
     */
    'disk' => 'snapshots',

    /*
     * The connection to be used to create snapshots. Set this to null
     * to use the default configured in `config/databases.php`
     */
    'default_connection' => null,

    /*
     * The directory where temporary files will be stored.
     */
    'temporary_directory_path' => storage_path('app/laravel-db-snapshots/temp'),

    /*
     * Create dump files that are gzipped
     */
    'compress' => false,

    /*
     * Only these tables will be included in the snapshot. Set to `null` to include all tables.
     *
     * Default: `null`
     */
    'tables' => null,

    /*
     * All tables will be included in the snapshot expect this tables. Set to `null` to include all tables.
     *
     * Default: `null`
     */
    'exclude' => null,

    'addExtraOption' => '--no-owner --no-acl --no-privileges -Z 9 -Fc',
];

```

## Testing

```bash
composer test
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
