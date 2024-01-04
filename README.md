[![Latest Version on Packagist](https://img.shields.io/packagist/v/weslinkde/laravel-postgres-tools.svg?style=flat-square)](https://packagist.org/packages/weslinkde/laravel-postgres-tools)
[![GitHub Code Style Action Status](https://github.com/weslinkde/laravel-postgres-tools/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=master)](https://github.com/weslinkde/laravel-postgres-tools/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/weslinkde/laravel-postgres-tools.svg?style=flat-square)](https://packagist.org/packages/weslinkde/laravel-postgres-tools)

This package provides some tools to make working with postgres easier.
It comes with a command to create a snapshot of your database and a command to restore a snapshot.
Big thanks to [Spatie](https://spatie.be) for their great packages, especially
the [laravel-db-snapshots](https://github.com/spatie/laravel-db-snapshots) package, which we use to create snapshots.
You can also create new databases, drop existing ones or clone them.

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
    'default_connection' => 'pgsql',

    /*
     * The directory where temporary files will be stored.
     */
    'temporary_directory_path' => storage_path('app/laravel-db-snapshots/temp'),

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

    /*
     * These are the options that will be passed to `pg_dump`. See `man pg_dump` for more information.
     */
    'addExtraOption' => '--no-owner --no-acl --no-privileges -Z 9 -Fc',
];

```

## Usage

To create a snapshot (which is just a dump from the database), run:

```bash
php artisan weslink:snapshot:create my-first-dump
```

Giving your snapshot a name is optional. If you don't pass a name, the current date time will be used:

```bash
# Creates a snapshot named something like `2017-03-17 14:31`
php artisan weslink:snapshot:create
```

Maybe you only want to snapshot a couple of tables.
You can do this by passing the `--table` multiple times or as a comma separated list:

```bash
# Both commands create a snapshot containing only the posts and users tables:
php artisan weslink:snapshot:create --table=posts,users
php artisan weslink:snapshot:create --table=posts --table=users
```

You may want to exclude some tables from snapshot.
You can do this by passing the `--exclude` multiple times or as a comma separated list:

```bash
# create snapshot from all tables excluding the users and posts
php artisan weslink:snapshot:create --exclude=posts,users
php artisan weslink:snapshot:create --exclude=posts --exclude=users
```

> Note: if you pass `--table` and `--exclude` in the same time it will use `--table` to create the snapshot, and it'd
> ignore the `--exclude`

After you've made some changes to the database, you can create another snapshot:

```bash
php artisan snapshot:create my-second-dump
```

To load a previous dump issue, this command:

```bash
php artisan snapshot:load my-first-dump
```

To load a previous dump to another DB connection (but the driver has to be pgsql):

```bash
php artisan snapshot:load my-first-dump --connection=connectionName
```

A dump can be deleted with:

```bash
php artisan snapshot:delete my-first-dump
```

You can create new databases with:

```bash
php artisan snapshot:database:create my-new-database
```

And you can drop existing databases with:

```bash
php artisan snapshot:database:drop my-old-database
```

> Note: This action is irreversible. It will drop the database, on production it will ask you for your confirmation.

It is also possible to clone an existing database with:

```bash
php artisan snapshot:database:clone my-old-database my-new-database
```

It will create a new database for you. If the new database already exists, it won't do anything.

## Events

For convenience, we're using the events from Spaties package.
There are several events fired that can be used to perform some logic of your own:

- `Spatie\DbSnapshots\Events\CreatingSnapshot`: will be fired before a snapshot is created
- `Spatie\DbSnapshots\Events\CreatedSnapshot`: will be fired after a snapshot has been created
- `Spatie\DbSnapshots\Events\LoadingSnapshot`: will be fired before a snapshot is loaded
- `Spatie\DbSnapshots\Events\LoadedSnapshot`: will be fired after a snapshot has been loaded
- `Spatie\DbSnapshots\Events\DeletingSnapshot`: will be fired before a snapshot is deleted
- `Spatie\DbSnapshots\Events\DeletedSnapshot`: will be fired after a snapshot has been deleted

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
