<?php

declare(strict_types=1);

/**
 * Append-only operational logger for backup jobs (DB + optional file trail).
 */
class BackupLogger
{
    public static function append(PDO $pdo, int $backupId, string $message, array $context = []): void
    {
        $row = BackupRepository::findById($pdo, $backupId);
        if ($row === null) {
            return;
        }

        $existing = [];
        if (!empty($row['log_detail'])) {
            $decoded = json_decode((string) $row['log_detail'], true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $existing[] = [
            'at' => date('Y-m-d H:i:s'),
            'message' => $message,
            'context' => $context,
        ];

        BackupRepository::update($pdo, $backupId, [
            'log_detail' => json_encode($existing, JSON_UNESCAPED_UNICODE),
        ]);

        $line = sprintf(
            "[%s] backup#%d %s %s\n",
            date('Y-m-d H:i:s'),
            $backupId,
            $message,
            $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        $logFile = self::logFilePath();
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public static function logFilePath(): string
    {
        return dirname(__DIR__) . '/storage/backups/backup-system.log';
    }
}
