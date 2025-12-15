<?php

return [
    'disk' => 'snapshots',
    'default_connection' => 'pgsql',
    'temporary_directory_path' => storage_path('app/postgres-tools/temp'),
    'tables' => null,
    'exclude' => null,
    'addExtraOption' => '--no-owner --no-acl --no-privileges -Z 3 -Fc',
    'jobs' => 4,
];
