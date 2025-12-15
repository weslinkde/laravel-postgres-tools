<?php

return [
    'default' => 'local',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'snapshots' => [
            'driver' => 'local',
            'root' => storage_path('app/snapshots'),
        ],
    ],
];
