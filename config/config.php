<?php
return [
    'app_name' => 'Transport Management System',
    'env' => 'local',
    'debug' => true,

    // Database
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'tms_db',
        'username' => 'root',
        'password' => '1234',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    // Security
    'session_name' => 'tms_session',
    'csrf_key' => 'change-this-to-a-random-secret-key',

    // App
    'main_branch_code' => 'MAIN', // Used for seeding/identifying main branch

    // Company branding (used in print views)
    'company' => [
        'name' => 'TS Transport',
        // Optional: place a logo image in public/ (e.g., public/logo.png) and set the URL below
        'logo_url' => '', // e.g., '/TMS/public/logo.png'
        'addresses' => [
            'Colombo: No. 71A, Wolfendhal Street, Colombo 13 | 077 2474905, 077 2474177',
            'Kilinochchi: Paravipanchan, Kilinochchi | 021 720 1757, 0772474605',
            'Mullaitivu: Oddusuddan Road, Puthukudiyiruppu, Mullaitivu | 077 2474205,   077 2480830',
        ],
        'footer_note' => 'Goods received in good condition. Subject to company terms.'
    ],

    // Integrations
    // Provide your Google Maps API key to enable Places Autocomplete on Delivery Location fields
    'google_maps_api_key' => '', // e.g., 'AIza...'
];
