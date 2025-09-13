<?php
// Basic bootstrap for the TMS application

// Strict types and error reporting for development
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_name((require __DIR__ . '/../config/config.php')['session_name'] ?? 'tms_session');
session_start();

// Autoload basic classes (simple)
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Helpers.php';

// Load config and initialize DB
$config = require __DIR__ . '/../config/config.php';
Database::init($config);
