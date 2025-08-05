<?php

return [

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

    'auth' => [
        'dynamodb' => [
            'table' => env('WORLDPAY_CREDENTIALS_TABLE_NAME'),
            'region' => env('WORLDPAY_CREDENTIALS_AWS_REGION'),
        ],
    ],
    'service_url' => env('WORLDPAY_API_SERVICE_URL', 'https://certservices.elementexpress.com'),
    'transaction_url' => env('WORLDPAY_API_TRANSACTION_URL', 'https://certtransaction.elementexpress.com'),
    'timeout' => env('WORLDPAY_API_TIMEOUT', \App\Repositories\WorldPay\WorldPayBaseRepository::REQUEST_TIMEOUT),
    'company_name' => env('WORLDPAY_COMPANY_NAME', 'Aptive'),
    'application' => [
        'application_id' =>  env('WORLDPAY_APPLICATION_ID', '8704'),
        'application_name' => env('WORLDPAY_APPLICATION_TOKEN', 'Aptive'),
        'application_version' => env('WORLDPAY_APPLICATION_VERSION', '1.00'),
    ],
    'payment_account' => [
        'payment_account_type' => env('WORLDPAY_PAYMENT_ACCOUNT_TYPE', '0'),
        'payment_account_reference_number' => env('WORLDPAY_PAYMENT_ACCOUNT_REFERENCE_NUMBER', '767344'),
    ],
    'transaction_setup_url' => env('WORLDPAY_TRANSACTION_SETUP_URL', 'https://certtransaction.hostedpayments.com/?TransactionSetupID={{TransactionSetupID}}'),
    'transaction_setup' => [
        'callback_url' => env('WORLDPAY_TRANSACTION_SETUP_CALLBACK_URL', 'https://app.customer-portal.stg.goaptive.com/worldpay/transaction-setup-callback'),
    ],
];
