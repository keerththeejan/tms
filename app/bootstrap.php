<?php
// Basic bootstrap for the TMS application

// Strict types and error reporting for development
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_name((require __DIR__ . '/../config/config.php')['session_name'] ?? 'tms_session');
session_start();

// Load Composer autoloader if available (for PHPMailer, etc.)
$vendor = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendor)) {
    require_once $vendor;
}

// Autoload basic classes (simple)
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Mailer.php';

// Load config and initialize DB
$config = require __DIR__ . '/../config/config.php';
Database::init($config);

// Initialize Mailer (available as $GLOBALS['mailer'])
try {
    $mailConfig = require __DIR__ . '/../config/mail.php';
} catch (Throwable $e) {
    $mailConfig = [
        'use_smtp' => false,
        'from_email' => 'no-reply@example.com',
        'from_name' => 'TMS Notifications',
    ];
}
$GLOBALS['mailer'] = new Mailer($mailConfig);
