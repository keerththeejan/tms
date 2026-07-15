<?php

declare(strict_types=1);

/**
 * Enterprise Backup Management configuration.
 * Google Drive credentials live in a separate JSON file (never commit secrets).
 */
return [
    // Local storage (relative to project root)
    'storage_path' => 'storage/backups',
    'progress_path' => 'storage/backups/.progress',
    'lock_path' => 'storage/backups/.locks',

    // mysqldump binary — leave empty to auto-detect (WAMP / PATH)
    'mysqldump_path' => 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe',
    'mysql_path' => 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysql.exe',

    // Schedule (server timezone — Asia/Colombo via public/index.php)
    'schedule_hour' => 2,
    'schedule_minute' => 0,
    'filename_prefix' => 'TMS_Backup_',

    // Retention
    'keep_daily' => 30,
    'keep_monthly' => 12,

    // Google Drive settings live in config/google-drive.php (merged at runtime).
    // Keep this block for backwards compatibility; dedicated file wins on conflicts.
    'google_drive' => [
        'enabled' => true,
        'folder_name' => 'TMS Database Backups',
        'credentials_path' => 'config/google-drive-service-account.json',
        'folder_id' => '',
        'retry_upload' => true,
        'max_upload_retries' => 5,
        'upload_timeout' => 300,
    ],

    // Email notifications
    'email' => [
        'enabled' => true,
        // Empty = use mail.php from_email
        'admin_email' => '',
        'admin_name' => 'TMS Administrator',
    ],

    // Optional future: file/document/image backups
    'include_uploads' => false,
];
