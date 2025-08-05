<?php

declare(strict_types=1);

return [
    'default' => env('METRICS_BACKEND', 'log'),
    'backends' => [
        'http' => [
            'url' => env('METRICS_HTTP_URL'),
            'token' => env('METRICS_HTTP_TOKEN'),
            'timeout' => env('METRICS_HTTP_TIMEOUT', 5),
        ],
        'log' => [],
    ],
];
