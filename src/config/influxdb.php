<?php

declare(strict_types=1);

return [
    'connection' => [
        'host' => env('INFLUXDB_HOST'),
        'token' => env('INFLUXDB_TOKEN'),
        'bucket' => env('INFLUXDB_BUCKET'),
        'organization' => env('INFLUXDB_ORGANIZATION'),

    ]
];
