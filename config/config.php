<?php
$config = [
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
        'reg_no' => 'KN/KR/1443',
        // Optional: place a logo image in public/ (e.g., public/logo.png) and set the URL below
        'logo_url' => '', // e.g., '/TMS/public/logo.png'
        // Route bar: Tamil names with yellow arrows between (Colombo ⟷ Kilinochchi ⟷ Mullaitivu)
        'route_tamil_parts' => ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'],
        // Branch details for bill header (Tamil + English addresses, phones)
        'branches' => [
            [
                'name' => 'Colombo',
                'address_ta' => 'இல. 71 A, ஆட்டுப்பட்டித்தெரு, கொழும்பு - 13.',
                'address_en' => 'No. 71A, Wolfendhal Street, Colombo - 13.',
                'phones' => '077 2474 905 | 077 2474 177',
            ],
            [
                'name' => 'Kilinochchi',
                'address_ta' => 'பரவிப்பாஞ்சான், கிளிநொச்சி.',
                'address_en' => 'Paravippanchan, Kilinochchi.',
                'phones' => '021 720 1757 | 077 2474 605',
            ],
            [
                'name' => 'Mullaitivu',
                'address_ta' => 'ஒட்டுசுட்டான் வீதி, புதுக்குடியிருப்பு, முல்லைத்தீவு.',
                'address_en' => 'Oddusuddaan Road, Puthukudiyiruppu, Mullaitivu.',
                'phones' => '077 2474 205 | 077 2480 830',
            ],
        ],
        // Legacy: single-line addresses (used if branches not set)
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

// Override company (and optional keys) from config/company.json if present (saved via Settings page)
$companyJsonPath = __DIR__ . '/company.json';
if (file_exists($companyJsonPath)) {
    $overrides = @json_decode((string)file_get_contents($companyJsonPath), true);
    if (is_array($overrides)) {
        if (isset($overrides['company']) && is_array($overrides['company'])) {
            $config['company'] = array_merge($config['company'] ?? [], $overrides['company']);
        }
        if (isset($overrides['google_maps_api_key'])) {
            $config['google_maps_api_key'] = $overrides['google_maps_api_key'];
        }
    }
}

return $config;
    