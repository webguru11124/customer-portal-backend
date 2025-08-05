<?php

declare(strict_types=1);

return [
    'api_url' => env('PB_API_URL', 'https://api.plan-builder.stg.goaptive.com/api'),
    'api_key' => env('PB_API_KEY', '1|api_kei'),
    'customer_portal' => [
        'category_name' => 'Customer Portal',
        'active_status_name' => 'Active',
        'low_pricing_level_name' => 'Low',
        'plans' => [
            'Premium' => '',
            'Pro +' => '',
            'Pro' => '',
            'Basic' => '',
        ],
    ],
];
