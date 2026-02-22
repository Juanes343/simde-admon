<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DataIco Electronic Invoicing Configuration
    |--------------------------------------------------------------------------
    */

    'dataico' => [
        'base_url' => env('DATAICO_BASE_URL', 'https://api.dataico.com/direct/dataico_api/v2'),
        'token' => env('DATAICO_TOKEN', '0a7c5d5e2003f8e957288231af3c4ef5'),
        'account_id' => env('DATAICO_ACCOUNT_ID', '01808624-3175-8838-83d4-1db98f4da325'),
            'env' => env('DATAICO_ENV', 'PRODUCCION'), // PRUEBAS o PRODUCTO
            'send_dian' => env('DATAICO_SEND_DIAN', false),
        
        'prefixes' => [
            'invoice' => env('DATAICO_PREFIX_INVOICE', 'FE'),
            'credit_note' => env('DATAICO_PREFIX_NC', 'NCSET'),
            'debit_note' => env('DATAICO_PREFIX_ND', 'NDSETT'),
        ],
        
        'resolutions' => [
            'invoice' => env('DATAICO_RESOLUTION_INVOICE', '18760000000001'),
        ],

        'nit_sender' => env('DATAICO_NIT_SENDER', '800230028'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
