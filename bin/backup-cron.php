#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Enterprise daily backup cron entrypoint.
 *
 * Linux crontab (server timezone = Asia/Colombo):
 *   0 2 * * * /usr/bin/php /var/www/tms/bin/backup-cron.php >> /var/www/tms/storage/backups/cron.log 2>&1
 *
 * Prevents duplicate successful backups on the same calendar day.
 */

$root = dirname(__DIR__);
require_once $root . '/app/bootstrap.php';

date_default_timezone_set('Asia/Colombo');

$log = static function (string $message): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    echo $line;
    $dir = dirname(__DIR__) . '/storage/backups';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/cron.log', $line, FILE_APPEND | LOCK_EX);
};

try {
    $pdo = Database::pdo();
    BackupSchemaRepository::ensureSchema($pdo);
    $result = BackupScheduler::runFromCron($pdo);
    $log('result=' . json_encode($result, JSON_UNESCAPED_UNICODE));
    exit(!empty($result['ran']) && !empty($result['result']['ok']) ? 0 : 0);
} catch (Throwable $e) {
    $log('FATAL: ' . $e->getMessage());
    exit(1);
}
