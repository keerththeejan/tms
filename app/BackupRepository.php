<?php

declare(strict_types=1);

class BackupRepository
{
    public static function create(PDO $pdo, array $data): int
    {
        $st = $pdo->prepare(
            'INSERT INTO backup_logs
             (filename, filepath, size_bytes, duration_seconds, status, backup_type, destination,
              google_drive_file_id, google_drive_link, google_drive_status, google_drive_error,
              google_drive_uploaded_at, upload_retries, error_message, log_detail, progress_percent, progress_message,
              created_by, created_by_name, ip_address, created_at, completed_at)
             VALUES
             (:filename, :filepath, :size_bytes, :duration_seconds, :status, :backup_type, :destination,
              :google_drive_file_id, :google_drive_link, :google_drive_status, :google_drive_error,
              :google_drive_uploaded_at, :upload_retries, :error_message, :log_detail, :progress_percent, :progress_message,
              :created_by, :created_by_name, :ip_address, :created_at, :completed_at)'
        );
        $st->execute([
            'filename' => (string) ($data['filename'] ?? ''),
            'filepath' => $data['filepath'] ?? null,
            'size_bytes' => (int) ($data['size_bytes'] ?? 0),
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'status' => (string) ($data['status'] ?? 'PENDING'),
            'backup_type' => (string) ($data['backup_type'] ?? 'MANUAL'),
            'destination' => (string) ($data['destination'] ?? 'LOCAL'),
            'google_drive_file_id' => $data['google_drive_file_id'] ?? null,
            'google_drive_link' => $data['google_drive_link'] ?? null,
            'google_drive_status' => (string) ($data['google_drive_status'] ?? 'PENDING'),
            'google_drive_error' => $data['google_drive_error'] ?? null,
            'google_drive_uploaded_at' => $data['google_drive_uploaded_at'] ?? null,
            'upload_retries' => (int) ($data['upload_retries'] ?? 0),
            'error_message' => $data['error_message'] ?? null,
            'log_detail' => $data['log_detail'] ?? null,
            'progress_percent' => (int) ($data['progress_percent'] ?? 0),
            'progress_message' => $data['progress_message'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_by_name' => $data['created_by_name'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'created_at' => (string) ($data['created_at'] ?? date('Y-m-d H:i:s')),
            'completed_at' => $data['completed_at'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        if ($id <= 0 || $data === []) {
            return;
        }

        $allowed = [
            'filename', 'filepath', 'size_bytes', 'duration_seconds', 'status', 'backup_type',
            'destination', 'google_drive_file_id', 'google_drive_link', 'google_drive_status',
            'google_drive_error', 'google_drive_uploaded_at', 'upload_retries', 'error_message', 'log_detail',
            'progress_percent', 'progress_message', 'completed_at',
        ];

        $sets = [];
        $params = ['id' => $id];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[] = "{$col} = :{$col}";
                $params[$col] = $data[$col];
            }
        }
        if ($sets === []) {
            return;
        }

        $sql = 'UPDATE backup_logs SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $st = $pdo->prepare($sql);
        $st->execute($params);
    }

    public static function findById(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM backup_logs WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public static function findByFilename(PDO $pdo, string $filename): ?array
    {
        $st = $pdo->prepare('SELECT * FROM backup_logs WHERE filename = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$filename]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public static function listRecent(PDO $pdo, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $st = $pdo->query(
            'SELECT * FROM backup_logs ORDER BY created_at DESC, id DESC LIMIT ' . $limit
        );

        return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public static function countAll(PDO $pdo): int
    {
        return (int) $pdo->query('SELECT COUNT(*) FROM backup_logs')->fetchColumn();
    }

    public static function countSuccessful(PDO $pdo): int
    {
        return (int) $pdo->query(
            "SELECT COUNT(*) FROM backup_logs WHERE status IN ('SUCCESS','PARTIAL')"
        )->fetchColumn();
    }

    public static function getLastSuccessful(PDO $pdo): ?array
    {
        $st = $pdo->query(
            "SELECT * FROM backup_logs
             WHERE status IN ('SUCCESS','PARTIAL')
             ORDER BY completed_at DESC, id DESC
             LIMIT 1"
        );
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;

        return $row !== false ? $row : null;
    }

    public static function hasSuccessfulBackupToday(PDO $pdo, ?string $date = null): bool
    {
        $date = $date ?? date('Y-m-d');
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM backup_logs
             WHERE DATE(created_at) = ?
               AND status IN ('SUCCESS','PARTIAL')
               AND backup_type IN ('AUTO','MANUAL')"
        );
        $st->execute([$date]);

        return (int) $st->fetchColumn() > 0;
    }

    public static function hasRunningBackup(PDO $pdo): bool
    {
        $st = $pdo->query(
            "SELECT COUNT(*) FROM backup_logs
             WHERE status = 'RUNNING'
               AND created_at >= (NOW() - INTERVAL 2 HOUR)"
        );

        return $st !== false && (int) $st->fetchColumn() > 0;
    }

    /** @return list<array<string, mixed>> */
    public static function listForRetention(PDO $pdo): array
    {
        $st = $pdo->query(
            "SELECT * FROM backup_logs
             WHERE status IN ('SUCCESS','PARTIAL')
               AND backup_type IN ('AUTO','MANUAL')
             ORDER BY created_at DESC, id DESC"
        );

        return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /** @return list<array<string, mixed>> */
    public static function listPendingDriveUploads(PDO $pdo, int $maxRetries = 3): array
    {
        $st = $pdo->prepare(
            "SELECT * FROM backup_logs
             WHERE status IN ('SUCCESS','PARTIAL')
               AND google_drive_status IN ('PENDING','FAILED','RETRY','SKIPPED')
               AND (google_drive_file_id IS NULL OR google_drive_file_id = '')
               AND upload_retries < ?
               AND filepath IS NOT NULL
             ORDER BY id ASC
             LIMIT 20"
        );
        $st->execute([$maxRetries]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function delete(PDO $pdo, int $id): void
    {
        $st = $pdo->prepare('DELETE FROM backup_logs WHERE id = ?');
        $st->execute([$id]);
    }

    public static function getLastDriveUpload(PDO $pdo): ?array
    {
        $st = $pdo->query(
            "SELECT * FROM backup_logs
             WHERE google_drive_status = 'UPLOADED'
             ORDER BY COALESCE(google_drive_uploaded_at, completed_at) DESC, id DESC
             LIMIT 1"
        );
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : false;

        return $row !== false ? $row : null;
    }

    public static function sumLocalSize(PDO $pdo): int
    {
        return (int) $pdo->query(
            "SELECT COALESCE(SUM(size_bytes), 0) FROM backup_logs
             WHERE status IN ('SUCCESS','PARTIAL')"
        )->fetchColumn();
    }
}
