<?php

declare(strict_types=1);

/**
 * Daily backup scheduler: cron primary, web-traffic fallback.
 * Never creates duplicate successful backups on the same calendar day.
 */
class BackupScheduler
{
    /**
     * Invoked from Linux cron / Windows Task Scheduler.
     *
     * @return array{ran:bool, result?:array, reason?:string}
     */
    public static function runFromCron(PDO $pdo): array
    {
        return self::run($pdo, true);
    }

    /**
     * Lightweight hook for HTTP traffic — only runs after schedule hour if
     * no successful backup exists for today. Uses a lock file to prevent races.
     *
     * @return array{ran:bool, result?:array, reason?:string}
     */
    public static function tickFromWeb(PDO $pdo): array
    {
        try {
            BackupSchemaRepository::ensureSchema($pdo);
        } catch (Throwable $e) {
            return ['ran' => false, 'reason' => 'schema'];
        }

        if (BackupRepository::hasSuccessfulBackupToday($pdo)) {
            return ['ran' => false, 'reason' => 'already_backed_up_today'];
        }

        $cfg = GoogleDriveService::loadConfig();
        $hour = (int) ($cfg['schedule_hour'] ?? 2);
        $minute = (int) ($cfg['schedule_minute'] ?? 0);
        $nowH = (int) date('G');
        $nowM = (int) date('i');
        if ($nowH < $hour || ($nowH === $hour && $nowM < $minute)) {
            return ['ran' => false, 'reason' => 'before_schedule_time'];
        }

        return self::run($pdo, false);
    }

    /**
     * @return array{ran:bool, result?:array, reason?:string}
     */
    private static function run(PDO $pdo, bool $fromCron): array
    {
        $cfg = GoogleDriveService::loadConfig();
        $lockDir = dirname(__DIR__) . '/' . ($cfg['lock_path'] ?? 'storage/backups/.locks');
        $lockDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $lockDir);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }

        $lockFile = $lockDir . DIRECTORY_SEPARATOR . 'daily.lock';
        $fp = @fopen($lockFile, 'c+');
        if ($fp === false) {
            return ['ran' => false, 'reason' => 'lock_open_failed'];
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);

            return ['ran' => false, 'reason' => 'already_running'];
        }

        try {
            BackupSchemaRepository::ensureSchema($pdo);

            if (BackupRepository::hasSuccessfulBackupToday($pdo)) {
                self::logExecution('skip_duplicate', $fromCron);

                return ['ran' => false, 'reason' => 'already_backed_up_today'];
            }

            if (BackupRepository::hasRunningBackup($pdo)) {
                self::logExecution('skip_running', $fromCron);

                return ['ran' => false, 'reason' => 'backup_running'];
            }

            // Re-check schedule window for cron (cron should fire at 02:00; still OK anytime)
            self::logExecution('start', $fromCron);
            $service = new BackupService($pdo, $cfg);
            $result = $service->runBackup(
                'AUTO',
                null,
                $fromCron ? 'CRON' : 'SCHEDULER',
                $fromCron ? 'cron' : self::clientIp()
            );
            self::logExecution($result['ok'] ?? false ? 'success' : 'failed', $fromCron, $result);

            return ['ran' => true, 'result' => $result];
        } catch (Throwable $e) {
            self::logExecution('exception', $fromCron, ['error' => $e->getMessage()]);

            return ['ran' => false, 'reason' => $e->getMessage()];
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private static function logExecution(string $event, bool $fromCron, array $context = []): void
    {
        $dir = dirname(__DIR__) . '/storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf(
            "[%s] cron=%s event=%s %s\n",
            date('Y-m-d H:i:s'),
            $fromCron ? '1' : '0',
            $event,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($dir . '/scheduler.log', $line, FILE_APPEND | LOCK_EX);
    }

    private static function clientIp(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }
}
