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
            'Colombo: No:71A, Welikadé, Colombo 13 | 077-2345678',
            'Kilinochchi: Parameshwaran, Kilinochchi | 021-720 1757',
            'Vavuniya: Oddusuddan Rd, Vavuniya | 024-222 3456',
        ],
        'footer_note' => 'Goods received in good condition. Subject to company terms.'
    ],

    // Integrations
    // Provide your Google Maps API key to enable Places Autocomplete on Delivery Location fields
    'google_maps_api_key' => '', // e.g., 'AIza...'
];
