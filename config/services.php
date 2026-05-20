<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'nvd' => [
        'api_key' => env('NVD_API_KEY'),
    ],
    'glpi' => [
        'url' => env('GLPI_API_URL'),
        'app_token' => env('GLPI_APP_TOKEN'),
        'user_token' => env('GLPI_USER_TOKEN'),
    ],

    'shodan' => [
        'base_url' => env('SHODAN_BASE_URL', 'https://api.shodan.io'),
        'internetdb_base_url' => env('SHODAN_INTERNETDB_BASE_URL', 'https://internetdb.shodan.io'),
    ],

    'abuseipdb' => [
        'key' => env('ABUSEIPDB_API_KEY'),
        'base_url' => env('ABUSEIPDB_BASE_URL', 'https://api.abuseipdb.com/api/v2'),
    ],

    'nvd' => [
        'base_url' => env('NVD_API_BASE_URL', 'https://services.nvd.nist.gov/rest/json/cves/2.0'),
        'api_key' => env('NVD_API_KEY'),
        'timeout_seconds' => env('NVD_TIMEOUT_SECONDS', 15),
        'cache_days' => env('NVD_CACHE_DAYS', 7),
    ],

    'epss' => [
        'base_url' => env('EPSS_API_BASE_URL', 'https://api.first.org/data/v1/epss'),
        'timeout_seconds' => env('EPSS_TIMEOUT_SECONDS', 15),
        'cache_days' => env('EPSS_CACHE_DAYS', 1),
    ],

];
