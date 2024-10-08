<?php

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
    'tables' => env('PG_INCLUDE_TABLES', null),

    /*
     * All tables will be included in the snapshot expect this tables. Set to `null` to include all tables.
     *
     * Default: `null`
     */
    'exclude' => env('PG_EXCLUDE_TABLES', null),

    /*
     * These are the options that will be passed to `pg_dump`. See `man pg_dump` for more information.
     */
    'addExtraOption' => '--no-owner --no-acl --no-privileges -Z 9 -Fc',

    /*
     * The number jobs pg_restore should use to restore the snapshot.
     */
    'jobs' => env('PG_RESTORE_JOBS', 1),
];
