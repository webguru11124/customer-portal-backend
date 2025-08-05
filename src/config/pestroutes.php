<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | This option controls the authentication keys and tokens used to authenticate
    | to the pestroutes api.
    */

    'auth' => [
        'dynamodb' => [
            'table' => env('PESTROUTES_CREDENTIALS_TABLE_NAME', 'credentials'),
            'region' => env('PESTROUTES_CREDENTIALS_AWS_REGION', 'us-east-1'),
        ],
        'global_office_id' => (int) env('PESTROUTES_GLOBAL_OFFICE_ID', 0),
        'main_office_id' => (int) env('PESTROUTES_MAIN_OFFICE_ID', 1),
    ],
    'max_office_id' => (int) env('PESTROUTES_MAX_OFFICE_ID', 999),
    /*
    |--------------------------------------------------------------------------
    | Subdomain URL
    |--------------------------------------------------------------------------
    |
    | This option controls the subdomain url for the pestroutes api. The URL for
    | the pestroutes API should exclude the specific endpoint.
    |
    | Example: https://subdomain.pestroutes.com/api
    |
    */

    'url' => env('PESTROUTES_API_URL', 'https://demoawsaptivepest.pestroutes.com/api/'),
    'timeout' => env('PESTROUTES_API_TIMEOUT', \App\Repositories\PestRoutes\AbstractPestRoutesRepository::REQUEST_TIMEOUT),

    'global_reservice_type_id' => env('GLOBAL_RESERVICE_TYPE_ID', 3),
];
