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
     *
     * Performance tips for large databases:
     * - Use -Z 1 or -Z 3 instead of -Z 9 for faster compression (3-5x faster with minimal size difference)
     * - -Fc format allows parallel restore with --jobs
     * - For 16GB+ databases, consider using directory format (-Fd) with parallel dumps
     */
    'addExtraOption' => env('PG_DUMP_OPTIONS', '--no-owner --no-acl --no-privileges -Z 3 -Fc'),

    /*
     * The number of jobs pg_restore should use to restore the snapshot.
     *
     * Recommended values:
     * - Small DBs (<1GB): 1-2 jobs
     * - Medium DBs (1-10GB): 4 jobs
     * - Large DBs (10GB+): 4-8 jobs (CPU cores - 2)
     *
     * Note: Only works with custom format (-Fc) or directory format (-Fd)
     */
    'jobs' => env('PG_RESTORE_JOBS', 4),
];
