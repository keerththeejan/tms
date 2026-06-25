<?php
// Basic bootstrap for the TMS application

// Strict types and error reporting for development
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
$isProduction = ($config['env'] ?? 'local') !== 'local';

if ($isProduction) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

session_name($config['session_name'] ?? 'tms_session');
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
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
require_once __DIR__ . '/ParcelSaveService.php';
require_once __DIR__ . '/ParcelBillingService.php';
require_once __DIR__ . '/BranchRepository.php';
require_once __DIR__ . '/BranchFixedMaster.php';
require_once __DIR__ . '/EmployeeListService.php';
require_once __DIR__ . '/EmployeeSchemaRepository.php';
require_once __DIR__ . '/EmployeePayrollService.php';
require_once __DIR__ . '/EmployeeRepository.php';
require_once __DIR__ . '/EmployeeApi.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/Sms.php';
require_once __DIR__ . '/DataReset.php';
require_once __DIR__ . '/CashbookRepository.php';
require_once __DIR__ . '/CashbookAccountService.php';
require_once __DIR__ . '/CashbookApi.php';
require_once __DIR__ . '/TransferVoucherRepository.php';
require_once __DIR__ . '/TransferVoucherApi.php';
require_once __DIR__ . '/AccountingSchemaRepository.php';
require_once __DIR__ . '/AccountGroupRepository.php';
require_once __DIR__ . '/AccountRepository.php';
require_once __DIR__ . '/AccountingVoucherRepository.php';
require_once __DIR__ . '/VoucherDetailRepository.php';
require_once __DIR__ . '/LedgerEntryRepository.php';
require_once __DIR__ . '/AccountingController.php';
require_once __DIR__ . '/AccountingDashboardSeedService.php';
require_once __DIR__ . '/AccountingModule.php';
require_once __DIR__ . '/TransportAccountingService.php';
require_once __DIR__ . '/ExpenseSchemaRepository.php';
require_once __DIR__ . '/ExpenseCategoryRepository.php';
require_once __DIR__ . '/ExpenseRepository.php';
require_once __DIR__ . '/ExpenseAccountingService.php';
require_once __DIR__ . '/ExpenseApi.php';
require_once __DIR__ . '/CustomerLedgerRepository.php';
require_once __DIR__ . '/AuditLogRepository.php';

// Load config and initialize DB (already loaded above)
Database::init($config);
try {
    BranchRepository::ensureSchema(Database::pdo());
} catch (Throwable $e) {
    /* schema optional until DB ready */
}
try {
    CashbookRepository::ensureSchema(Database::pdo());
} catch (Throwable $e) {
    /* cashbook optional until DB ready */
}
try {
    TransferVoucherRepository::ensureSchema(Database::pdo());
} catch (Throwable $e) {
    /* transfer vouchers optional until DB ready */
}
try {
    AccountGroupRepository::ensureSchema(Database::pdo());
    CustomerLedgerRepository::ensureSchema(Database::pdo());
    CustomerLedgerRepository::syncMissingIfNeeded(Database::pdo());
} catch (Throwable $e) {
    /* accounting module optional until DB ready */
}
try {
    ExpenseSchemaRepository::ensureSchema(Database::pdo());
} catch (Throwable $e) {
    /* expenses module optional until DB ready */
}
try {
    EmployeeSchemaRepository::ensureSchema(Database::pdo());
} catch (Throwable $e) {
    /* HRMS module optional until DB ready */
}

// Initialize Mailer (available as $GLOBALS['mailer'])
$mailCfgPath = __DIR__ . '/../config/mail.php';
if (file_exists($mailCfgPath)) {
    $mailConfig = require $mailCfgPath;
} else {
    $mailConfig = [
        'use_smtp' => false,
        'from_email' => 'no-reply@example.com',
        'from_name' => 'TMS Notifications',
    ];
}
$GLOBALS['mailer'] = new Mailer($mailConfig);

// Initialize SMS service
$smsCfgPath = __DIR__ . '/../config/sms.php';
if (file_exists($smsCfgPath)) {
    $smsConfig = require $smsCfgPath;
} else {
    $smsConfig = ['enabled' => false, 'provider' => 'http'];
}
$GLOBALS['sms'] = new Sms($smsConfig);
