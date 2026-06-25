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

    // Currency (Sri Lankan Rupee — single source of truth for all modules)
    'currency' => [
        'code' => 'LKR',
        'symbol' => 'LKR',
        'format' => 'LKR',
        'locale' => 'en-LK',
        'decimals' => 2,
    ],

    // Company branding (used in print views)
    'company' => [
        'name' => 'TS Transport',
        'reg_no' => 'KN/KR/1443',
        // Logo: 'image' = use logo_url / uploaded file; 'builtin' = red arch + bar style with logo_initials
        'logo_display' => 'builtin',
        'logo_initials' => 'TS',
        // Built-in logo colors (hex, no # required in settings)
        'logo_arch_color' => 'c00',
        'logo_bar_bg' => '000',
        'logo_bar_color' => 'fff',
        'logo_title_color' => 'c00',
        // Optional: place a logo image in public/ (e.g., public/logo.png) and set the URL below
        'logo_url' => '', // e.g., '/TMS/public/logo.png'
        // Route bar: Tamil names with yellow arrows between (Colombo ⟷ Kilinochchi ⟷ Mullaitivu)
        'route_tamil_parts' => ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'],
        // Branch letterhead data lives in the database (Settings → Company Address & Branch Management).
        // Empty fallback only when DB is unavailable; company.json may mirror slots after save.
        'branches' => [],
        // Legacy: single-line addresses (used only if DB branches are empty)
        'addresses' => [],
        'footer_note' => 'தரப்பட்ட பொருட்களைச் சரிபார்த்துப் பெற்றுக்கொள்வதுடன் 7 நாட்களுக்குள் பெற்றுக்கொள்ளாவிட்டால் நாம் பொறுப்பாளிகள் அல்ல'
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
    