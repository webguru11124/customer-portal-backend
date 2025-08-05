<?php

return [
    'notifications' => [
        'sendgrid_token' => env('SENDGRID_TOKEN'),
        'payment_info_link' => [
            'sms' => [
                'from_number' => env('TWILIO_API_NUMBER'),
            ],
            'email' => [
                'template_api_url' => env('SENDGRID_API_URL', 'https://api.sendgrid.com/v3/mail/send'),
                'template_id' => env('NOTIFICATIONS_PAYMENT_INFO_LINK_EMAIL_TEMPLATE_ID', 'd-d0a0d7c77f0244149c555230d31a7000'),
                'from_name' => env('SENDGRID_FROM_NAME', 'Aptive'),
                'from_email' => env('SENDGRID_FROM_EMAIL', 'no-reply@goaptive.com'),
            ],
        ],
    ],
    'transaction_setup_lifetime' => env('TRANSACTION_SETUP_LIFETIME', 3600),
    'available_spots_max_distance' => (int) env('AVAILABLE_SPOTS_MAX_DISTANCE', 3),

    'long_reservice_interval' => env('RESERVICE_INTERVAL_LONG', 61),
    'short_reservice_interval' => env('RESERVICE_INTERVAL_SHORT', 26),
    'basic_reservice_interval' => 39,
    'standard_treatment_duration' => env('TREATMENT_DURATION_STANDARD', 29),
    'reservice_treatment_duration' => env('TREATMENT_DURATION_RESERVICE', 20),

    'default_date_format' => 'Y-m-d',

    'summer_interval_service_types' => [
        'Pro' => 24,
        'Pro Plus' => 24,
        'Basic' => 39,
        'Premium' => 14,
    ],

    'short_interval_service_types' => [
        'Mosquito - Rain delay',
        'Mosquito Service',
        'Mosquito Service - 30 day',
        'Mosquito Service - 45 day',
    ],

    'mosquito_service_types' => [
        'Mosquito - Rain delay',
        'Mosquito Service',
        'Mosquito Service - 30 day',
        'Mosquito Service - 45 day',
    ],

    'cxp_scheduler_name' => ['fname' => 'CXP', 'lname' => 'Scheduler'],
    'service_type_mutual_office_id' => -1,

    'subscription' => [
        'frozen' => [
            'followupDelay' => -1,
            'isActive' => false,
            'flag' => env('PESTROUTES_FROZEN_SUBSCRIPTION_FLAG_ID'),
        ],
        'addons_default_values' => [
            'amount' => 0.00,
            'taxable' => true,
            'name' => 'Addon',
            'quantity' => 1,
            'service_id' => 0,
            'credit_to' => 0,
        ],
        'addons_exceptions' => [
            'disallowed_pests' => [
                'German Roach',
                'German Cockroach',
                'German Roaches',
            ]
        ]
    ],
];
