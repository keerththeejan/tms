<?php

declare(strict_types=1);

class BackupSchemaRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS backup_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(512) DEFAULT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  duration_seconds DECIMAL(10,2) DEFAULT NULL,
  status ENUM('PENDING','RUNNING','SUCCESS','FAILED','PARTIAL') NOT NULL DEFAULT 'PENDING',
  backup_type ENUM('AUTO','MANUAL','RESTORE_POINT') NOT NULL DEFAULT 'MANUAL',
  destination VARCHAR(64) NOT NULL DEFAULT 'LOCAL',
  google_drive_file_id VARCHAR(128) DEFAULT NULL,
  google_drive_link VARCHAR(512) DEFAULT NULL,
  google_drive_status ENUM('PENDING','UPLOADED','FAILED','SKIPPED','RETRY') NOT NULL DEFAULT 'PENDING',
  google_drive_error TEXT DEFAULT NULL,
  upload_retries TINYINT UNSIGNED NOT NULL DEFAULT 0,
  error_message TEXT DEFAULT NULL,
  log_detail LONGTEXT DEFAULT NULL,
  progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  progress_message VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_by_name VARCHAR(100) DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_backup_status (status),
  KEY idx_backup_created (created_at),
  KEY idx_backup_filename (filename),
  KEY idx_backup_type_date (backup_type, created_at),
  KEY idx_backup_gdrive_status (google_drive_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        self::ensureColumn(
            $pdo,
            'backup_logs',
            'google_drive_uploaded_at',
            'DATETIME NULL DEFAULT NULL AFTER google_drive_error'
        );
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $st->execute([$table, $column]);
        if ((int) $st->fetchColumn() > 0) {
            return;
        }
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}
