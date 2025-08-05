<?php

declare(strict_types=1);

return [
    'api_url' => env('APTIVE_PAYMENT_SERVICE_URL'),
    'api_key' => env('APTIVE_PAYMENT_SERVICE_API_KEY'),
    'api_token_scheme' => env('APTIVE_PAYMENT_SERVICE_TOKEN_SCHEME', 'PCI'),
];
