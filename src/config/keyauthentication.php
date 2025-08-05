<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Api Key Authentication
    |--------------------------------------------------------------------------
    |
    | This config controls the authentication keys and tokens used to authenticate
    | incoming API calls. The env value should be a base64 encoded json object containing the api key for each
    | app that will be using this api.
    |
    | The structure is as follows:
    |
    | {
    |   "uniqueApiKey": {
    |        "applicationName": "app Name"
    |    },
    |    "uniqueApiKey2": {
    |        "applicationName": "app Name 2"
    |    },
    |  }
    |
    | Example:
    | {
    |   "1234567890": {
    |        "applicationName": "SalesApp"
    |    }
    |  }
    |
    |
    */

    'apiKeys' => json_decode(base64_decode(env('API_KEYS_ALLOWED', 'e30=')), 1),

];
