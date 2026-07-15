<?php

declare(strict_types=1);

/**
 * Google Drive upload settings for TMS Backup.
 * Prefer editing this file for Drive options; credentials stay in a separate JSON key file.
 */
return [
    // Master switch — SKIPPED is only used when this is false
    'enabled' => true,

    // Human-readable folder name (used when folder_id is empty and folder must be created)
    'folder_name' => 'TMS Database Backups',

    /**
     * Target folder ID from Google Drive URL:
     * https://drive.google.com/drive/folders/FOLDER_ID_HERE
     *
     * Recommended: create the folder in your Google account, share it with the
     * service account email as Editor, then paste the folder ID here.
     */
    'folder_id' => getenv('TMS_GDRIVE_FOLDER_ID') ?: '',

    // Path to service-account JSON (relative to project root, or absolute)
    // Override with env GOOGLE_APPLICATION_CREDENTIALS
    'credentials_path' => getenv('GOOGLE_APPLICATION_CREDENTIALS')
        ?: 'config/google-drive-service-account.json',

    // Retry failed uploads after a successful local backup
    'retry_upload' => true,
    'max_upload_retries' => 5,

    // HTTP / upload timeout in seconds
    'upload_timeout' => 300,

    // OAuth scopes (drive recommended when using a shared user folder)
    'scopes' => [
        'https://www.googleapis.com/auth/drive',
    ],
];
