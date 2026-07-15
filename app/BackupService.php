<?php

declare(strict_types=1);

/**
 * Core backup engine: mysqldump → ZIP → retention → Google Drive → email.
 */
class BackupService
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, ?array $config = null)
    {
        $this->pdo = $pdo;
        $this->config = $config ?? GoogleDriveService::loadConfig();
        BackupSchemaRepository::ensureSchema($pdo);
        $this->ensureDirectories();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getBackupDirectory(): string
    {
        $rel = (string) ($this->config['storage_path'] ?? 'storage/backups');
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        return $path;
    }

    /**
     * @return array{ok:bool, backup_id?:int, filename?:string, error?:string}
     */
    public function runBackup(string $type = 'MANUAL', ?int $userId = null, ?string $userName = null, ?string $ip = null): array
    {
        $type = strtoupper($type);
        if (!in_array($type, ['AUTO', 'MANUAL', 'RESTORE_POINT'], true)) {
            $type = 'MANUAL';
        }

        if ($type === 'AUTO' && BackupRepository::hasSuccessfulBackupToday($this->pdo)) {
            return ['ok' => false, 'error' => 'A successful backup already exists for today.', 'duplicate' => true];
        }

        if (BackupRepository::hasRunningBackup($this->pdo)) {
            return ['ok' => false, 'error' => 'Another backup is already running.'];
        }

        $started = microtime(true);
        $filename = $this->buildFilename();
        $zipPath = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

        $backupId = BackupRepository::create($this->pdo, [
            'filename' => $filename,
            'filepath' => $zipPath,
            'status' => 'RUNNING',
            'backup_type' => $type,
            'destination' => 'LOCAL',
            'google_drive_status' => 'PENDING',
            'progress_percent' => 5,
            'progress_message' => 'Starting backup…',
            'created_by' => $userId,
            'created_by_name' => $userName ?? ($type === 'AUTO' ? 'SYSTEM' : 'Administrator'),
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        BackupLogger::append($this->pdo, $backupId, 'Backup started', ['type' => $type]);

        $sqlTemp = null;
        try {
            $this->setProgress($backupId, 15, 'Dumping MySQL database…');
            $sqlTemp = $this->createMysqlDump();
            BackupLogger::append($this->pdo, $backupId, 'mysqldump completed', [
                'size' => filesize($sqlTemp) ?: 0,
            ]);

            $this->setProgress($backupId, 45, 'Compressing ZIP archive…');
            $this->zipSqlDump($sqlTemp, $zipPath, basename($sqlTemp));
            @unlink($sqlTemp);
            $sqlTemp = null;

            if (!is_file($zipPath) || filesize($zipPath) <= 0) {
                throw new RuntimeException('ZIP archive was not created or is empty.');
            }

            $size = (int) filesize($zipPath);
            $this->setProgress($backupId, 60, 'Local backup saved');
            BackupRepository::update($this->pdo, $backupId, [
                'size_bytes' => $size,
                'filepath' => $zipPath,
            ]);
            BackupLogger::append($this->pdo, $backupId, 'ZIP created', ['bytes' => $size]);

            // Google Drive upload (SKIPPED only when explicitly disabled in config)
            $drive = new GoogleDriveService($this->config);
            $destination = 'LOCAL';
            $driveStatus = 'PENDING';
            $driveFileId = null;
            $driveLink = null;
            $driveError = null;
            $driveUploadedAt = null;

            if (!$drive->isEnabled()) {
                $driveStatus = 'SKIPPED';
                BackupLogger::append($this->pdo, $backupId, 'Google Drive skipped (disabled in configuration)');
            } else {
                $this->setProgress($backupId, 70, 'Uploading to Google Drive…');
                try {
                    if (!$drive->isConfigured()) {
                        throw new RuntimeException($drive->getConfigurationError());
                    }
                    $uploaded = $drive->uploadFile($zipPath, $filename);
                    $driveFileId = $uploaded['file_id'];
                    $driveLink = $uploaded['web_view_link'];
                    $driveStatus = 'UPLOADED';
                    $driveUploadedAt = date('Y-m-d H:i:s');
                    $destination = 'LOCAL+GOOGLE_DRIVE';
                    BackupLogger::append($this->pdo, $backupId, 'Uploaded to Google Drive', [
                        'file_id' => $driveFileId,
                        'link' => $driveLink,
                    ]);
                } catch (Throwable $e) {
                    $driveStatus = 'FAILED';
                    $driveError = $e->getMessage();
                    $destination = 'LOCAL';
                    BackupLogger::append($this->pdo, $backupId, 'Google Drive upload failed — local backup kept', [
                        'error' => $driveError,
                    ]);
                }
            }

            $this->setProgress($backupId, 85, 'Applying retention policy…');
            $this->applyRetention();

            $duration = round(microtime(true) - $started, 2);
            $finalStatus = ($driveStatus === 'FAILED') ? 'PARTIAL' : 'SUCCESS';

            BackupRepository::update($this->pdo, $backupId, [
                'status' => $finalStatus,
                'destination' => $destination,
                'google_drive_file_id' => $driveFileId,
                'google_drive_link' => $driveLink,
                'google_drive_status' => $driveStatus,
                'google_drive_error' => $driveError,
                'google_drive_uploaded_at' => $driveUploadedAt,
                'duration_seconds' => $duration,
                'progress_percent' => 100,
                'progress_message' => $finalStatus === 'SUCCESS'
                    ? 'Backup completed'
                    : 'Backup saved locally; Drive upload pending retry',
                'completed_at' => date('Y-m-d H:i:s'),
                'error_message' => $driveError,
            ]);

            BackupLogger::append($this->pdo, $backupId, 'Backup finished', [
                'status' => $finalStatus,
                'duration' => $duration,
                'google_drive_status' => $driveStatus,
            ]);

            $row = BackupRepository::findById($this->pdo, $backupId);
            if ($row !== null) {
                $this->sendNotification($row, $finalStatus === 'SUCCESS' || $finalStatus === 'PARTIAL');
            }

            // Retry older failed uploads when enabled
            $this->retryFailedUploads();

            return [
                'ok' => true,
                'backup_id' => $backupId,
                'filename' => $filename,
                'status' => $finalStatus,
                'size_bytes' => $size,
                'duration_seconds' => $duration,
                'google_drive_status' => $driveStatus,
                'google_drive_link' => $driveLink,
            ];
        } catch (Throwable $e) {
            if ($sqlTemp !== null && is_file($sqlTemp)) {
                @unlink($sqlTemp);
            }
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            $duration = round(microtime(true) - $started, 2);
            BackupRepository::update($this->pdo, $backupId, [
                'status' => 'FAILED',
                'duration_seconds' => $duration,
                'progress_percent' => 100,
                'progress_message' => 'Backup failed',
                'error_message' => $e->getMessage(),
                'google_drive_status' => 'PENDING',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            BackupLogger::append($this->pdo, $backupId, 'Backup failed', ['error' => $e->getMessage()]);

            $row = BackupRepository::findById($this->pdo, $backupId);
            if ($row !== null) {
                $this->sendNotification($row, false);
            }

            return [
                'ok' => false,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retry uploading one local backup to Google Drive.
     *
     * @return array{ok:bool, error?:string, backup?:array}
     */
    public function retryUpload(int $backupId): array
    {
        $row = BackupRepository::findById($this->pdo, $backupId);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Backup not found.'];
        }

        $drive = new GoogleDriveService($this->config);
        if (!$drive->isEnabled()) {
            return ['ok' => false, 'error' => 'Google Drive is disabled in configuration.'];
        }
        if (!$drive->isConfigured()) {
            return ['ok' => false, 'error' => $drive->getConfigurationError()];
        }

        $path = $this->resolveLocalPath($row);
        if ($path === null) {
            return ['ok' => false, 'error' => 'Local ZIP missing — cannot upload.'];
        }

        $retries = (int) ($row['upload_retries'] ?? 0) + 1;
        try {
            $uploaded = $drive->uploadFile($path, (string) $row['filename']);
            BackupRepository::update($this->pdo, $backupId, [
                'google_drive_file_id' => $uploaded['file_id'],
                'google_drive_link' => $uploaded['web_view_link'],
                'google_drive_status' => 'UPLOADED',
                'google_drive_error' => null,
                'google_drive_uploaded_at' => date('Y-m-d H:i:s'),
                'upload_retries' => $retries,
                'destination' => 'LOCAL+GOOGLE_DRIVE',
                'status' => 'SUCCESS',
                'error_message' => null,
            ]);
            BackupLogger::append($this->pdo, $backupId, 'Drive retry upload succeeded', [
                'attempt' => $retries,
                'file_id' => $uploaded['file_id'],
            ]);

            return ['ok' => true, 'backup' => BackupRepository::findById($this->pdo, $backupId)];
        } catch (Throwable $e) {
            BackupRepository::update($this->pdo, $backupId, [
                'google_drive_status' => 'RETRY',
                'google_drive_error' => $e->getMessage(),
                'upload_retries' => $retries,
            ]);
            BackupLogger::append($this->pdo, $backupId, 'Drive retry upload failed', [
                'attempt' => $retries,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function retryFailedUploads(): int
    {
        $driveCfg = (new GoogleDriveService($this->config))->getConfigValue('retry_upload', true);
        if (!$driveCfg) {
            return 0;
        }

        $max = (int) ((new GoogleDriveService($this->config))->getConfigValue('max_upload_retries', 5));
        $drive = new GoogleDriveService($this->config);
        if (!$drive->isEnabled() || !$drive->isConfigured()) {
            return 0;
        }

        $pending = BackupRepository::listPendingDriveUploads($this->pdo, $max);
        $done = 0;
        foreach ($pending as $row) {
            $result = $this->retryUpload((int) $row['id']);
            if (!empty($result['ok'])) {
                $done++;
            }
        }

        return $done;
    }

    public function applyRetention(): void
    {
        $keepDaily = (int) ($this->config['keep_daily'] ?? 30);
        $keepMonthly = (int) ($this->config['keep_monthly'] ?? 12);
        $rows = BackupRepository::listForRetention($this->pdo);
        if ($rows === []) {
            return;
        }

        $keepIds = [];
        $monthlyKept = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $created = strtotime((string) $row['created_at']);
            if ($created === false) {
                continue;
            }
            $ageDays = (int) floor((time() - $created) / 86400);

            if ($ageDays <= $keepDaily) {
                $keepIds[$id] = true;
                continue;
            }

            $ym = date('Y-m', $created);
            if (!isset($monthlyKept[$ym]) && count($monthlyKept) < $keepMonthly) {
                $monthlyKept[$ym] = $id;
                $keepIds[$id] = true;
            }
        }

        // Ensure we still retain up to keepMonthly distinct months from older set
        if (count($monthlyKept) < $keepMonthly) {
            foreach ($rows as $row) {
                $id = (int) $row['id'];
                if (isset($keepIds[$id])) {
                    continue;
                }
                $created = strtotime((string) $row['created_at']);
                if ($created === false) {
                    continue;
                }
                $ym = date('Y-m', $created);
                if (!isset($monthlyKept[$ym]) && count($monthlyKept) < $keepMonthly) {
                    $monthlyKept[$ym] = $id;
                    $keepIds[$id] = true;
                }
            }
        }

        $drive = new GoogleDriveService($this->config);
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (isset($keepIds[$id])) {
                continue;
            }
            $this->deleteBackupArtifacts($row, $drive, true);
        }
    }

    /**
     * @return array{ok:bool, error?:string}
     */
    public function deleteBackup(int $id, bool $fromDrive = true): array
    {
        $row = BackupRepository::findById($this->pdo, $id);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Backup not found.'];
        }
        $drive = new GoogleDriveService($this->config);
        $this->deleteBackupArtifacts($row, $drive, $fromDrive);
        BackupRepository::delete($this->pdo, $id);

        return ['ok' => true];
    }

    public function resolveLocalPath(array $row): ?string
    {
        $path = (string) ($row['filepath'] ?? '');
        if ($path !== '' && is_file($path)) {
            return $path;
        }
        $name = basename((string) ($row['filename'] ?? ''));
        if ($name === '') {
            return null;
        }
        $candidate = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . $name;

        return is_file($candidate) ? $candidate : null;
    }

    public function getDashboardStats(): array
    {
        $last = BackupRepository::getLastSuccessful($this->pdo);
        $drive = new GoogleDriveService($this->config);
        $driveSummary = $drive->getStatusSummary();

        $next = $this->nextScheduledAt();
        $localBytes = 0;
        foreach (glob($this->getBackupDirectory() . DIRECTORY_SEPARATOR . 'TMS_Backup_*.zip') ?: [] as $f) {
            $localBytes += (int) filesize($f);
        }

        $syncPending = count(BackupRepository::listPendingDriveUploads(
            $this->pdo,
            (int) $drive->getConfigValue('max_upload_retries', 5)
        ));

        $lastUpload = BackupRepository::getLastDriveUpload($this->pdo);

        return [
            'last_backup' => $last,
            'next_scheduled' => $next,
            'backup_status' => $last['status'] ?? 'NONE',
            'google_drive' => $driveSummary,
            'google_drive_connected' => !empty($driveSummary['connected']),
            'google_drive_folder_name' => $driveSummary['folder'] ?? 'TMS Database Backups',
            'google_drive_folder_id' => $driveSummary['folder_id'] ?? '',
            'last_drive_upload' => $lastUpload,
            'local_storage_bytes' => $localBytes,
            'local_storage_human' => $this->formatBytes($localBytes),
            'google_drive_sync' => $syncPending > 0 ? "{$syncPending} pending retry" : 'In sync',
            'total_backups' => BackupRepository::countAll($this->pdo),
            'successful_backups' => BackupRepository::countSuccessful($this->pdo),
            'schedule_label' => sprintf(
                'Daily at %02d:%02d server time',
                (int) ($this->config['schedule_hour'] ?? 2),
                (int) ($this->config['schedule_minute'] ?? 0)
            ),
        ];
    }

    public function nextScheduledAt(): string
    {
        $h = (int) ($this->config['schedule_hour'] ?? 2);
        $m = (int) ($this->config['schedule_minute'] ?? 0);
        $today = strtotime(sprintf('today %02d:%02d:00', $h, $m));
        if ($today === false) {
            return date('Y-m-d H:i:s');
        }
        if (time() >= $today) {
            return date('Y-m-d H:i:s', strtotime('+1 day', $today));
        }

        return date('Y-m-d H:i:s', $today);
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB', 'TB'];
        $v = $bytes / 1024;
        foreach ($units as $u) {
            if ($v < 1024) {
                return number_format($v, $v >= 10 ? 1 : 2) . ' ' . $u;
            }
            $v /= 1024;
        }

        return number_format($v, 2) . ' PB';
    }

    private function deleteBackupArtifacts(array $row, GoogleDriveService $drive, bool $fromDrive): void
    {
        $path = $this->resolveLocalPath($row);
        if ($path !== null) {
            @unlink($path);
        }
        if ($fromDrive && !empty($row['google_drive_file_id']) && $drive->isConfigured()) {
            $drive->deleteFile((string) $row['google_drive_file_id']);
        }
    }

    private function setProgress(int $backupId, int $percent, string $message): void
    {
        BackupRepository::update($this->pdo, $backupId, [
            'progress_percent' => max(0, min(100, $percent)),
            'progress_message' => $message,
        ]);
    }

    private function buildFilename(): string
    {
        $prefix = (string) ($this->config['filename_prefix'] ?? 'TMS_Backup_');

        return $prefix . date('Y-m-d_H-i-s') . '.zip';
    }

    private function createMysqlDump(): string
    {
        $appConfig = Helpers::config();
        $db = $appConfig['db'] ?? [];
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (int) ($db['port'] ?? 3306);
        $user = (string) ($db['username'] ?? 'root');
        $pass = (string) ($db['password'] ?? '');
        $dbname = (string) ($db['database'] ?? '');
        if ($dbname === '') {
            throw new RuntimeException('Database name is not configured.');
        }

        $mysqldump = $this->resolveMysqldumpPath($appConfig);
        $tmp = $this->getBackupDirectory() . DIRECTORY_SEPARATOR
            . '.tmp_dump_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.sql';

        $cmd = '"' . $mysqldump . '"'
            . ' --host=' . escapeshellarg($host)
            . ' --port=' . escapeshellarg((string) $port)
            . ' --user=' . escapeshellarg($user)
            . ' --single-transaction --quick --routines --triggers --events --hex-blob'
            . ' --result-file=' . escapeshellarg($tmp)
            . ' ' . escapeshellarg($dbname);

        putenv('MYSQL_PWD=' . $pass);
        $output = [];
        $ret = 0;
        @exec($cmd . ' 2>&1', $output, $ret);
        putenv('MYSQL_PWD');

        if (($ret !== 0 || !is_file($tmp) || filesize($tmp) <= 0) && $pass !== '') {
            // Fallback for environments where MYSQL_PWD is ignored (common on Windows)
            @unlink($tmp);
            $cmdPw = '"' . $mysqldump . '"'
                . ' --host=' . escapeshellarg($host)
                . ' --port=' . escapeshellarg((string) $port)
                . ' --user=' . escapeshellarg($user)
                . ' --password=' . escapeshellarg($pass)
                . ' --single-transaction --quick --routines --triggers --events --hex-blob'
                . ' --result-file=' . escapeshellarg($tmp)
                . ' ' . escapeshellarg($dbname);
            $output = [];
            $ret = 0;
            @exec($cmdPw . ' 2>&1', $output, $ret);
        }

        if ($ret !== 0 || !is_file($tmp) || filesize($tmp) <= 0) {
            @unlink($tmp);
            throw new RuntimeException(
                'mysqldump failed (exit ' . $ret . '). '
                . 'Ensure mysqldump is installed or set mysqldump_path in config/backup.php. '
                . implode(' ', array_slice($output, 0, 5))
            );
        }

        return $tmp;
    }

    private function zipSqlDump(string $sqlPath, string $zipPath, string $entryName): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP ZipArchive extension is required.');
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create ZIP archive.');
        }
        if (!$zip->addFile($sqlPath, $entryName)) {
            $zip->close();
            throw new RuntimeException('Unable to add SQL dump to ZIP.');
        }
        // Manifest for enterprise audit
        $manifest = json_encode([
            'app' => 'TMS',
            'created_at' => date('c'),
            'sql_entry' => $entryName,
            'php_version' => PHP_VERSION,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $zip->addFromString('backup-manifest.json', (string) $manifest);
        $zip->close();
    }

    private function resolveMysqldumpPath(array $appConfig): string
    {
        $configured = trim((string) ($this->config['mysqldump_path'] ?? ''));
        if ($configured === '') {
            $configured = trim((string) ($appConfig['mysqldump_path'] ?? ''));
        }
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [];
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'C:\\wamp64\\bin\\mysql\\mysql8.4.7\\bin\\mysqldump.exe';
            foreach (glob('C:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysqldump.exe') ?: [] as $p) {
                $candidates[] = $p;
            }
            foreach (glob('C:\\xampp\\mysql\\bin\\mysqldump.exe') ?: [] as $p) {
                $candidates[] = $p;
            }
            $candidates[] = 'mysqldump.exe';
            $candidates[] = 'mysqldump';
        } else {
            $candidates[] = '/usr/bin/mysqldump';
            $candidates[] = '/usr/local/bin/mysqldump';
            $candidates[] = 'mysqldump';
        }

        foreach ($candidates as $c) {
            if ($c === 'mysqldump' || $c === 'mysqldump.exe') {
                return $c;
            }
            if (is_file($c)) {
                return $c;
            }
        }

        return 'mysqldump';
    }

    private function sendNotification(array $row, bool $success): void
    {
        $emailCfg = $this->config['email'] ?? [];
        if (empty($emailCfg['enabled'])) {
            return;
        }
        /** @var Mailer|null $mailer */
        $mailer = $GLOBALS['mailer'] ?? null;
        if (!$mailer instanceof Mailer) {
            return;
        }

        $to = trim((string) ($emailCfg['admin_email'] ?? ''));
        if ($to === '') {
            $mailCfgPath = dirname(__DIR__) . '/config/mail.php';
            if (is_file($mailCfgPath)) {
                $m = require $mailCfgPath;
                $to = (string) ($m['from_email'] ?? '');
            }
        }
        if ($to === '') {
            return;
        }

        $name = (string) ($emailCfg['admin_name'] ?? 'TMS Administrator');
        $filename = htmlspecialchars((string) ($row['filename'] ?? ''), ENT_QUOTES, 'UTF-8');
        $size = $this->formatBytes((int) ($row['size_bytes'] ?? 0));
        $date = htmlspecialchars((string) ($row['completed_at'] ?? $row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars((string) ($row['google_drive_link'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8');

        if ($success) {
            $subject = 'Database Backup Completed Successfully';
            $html = '<h2>Database Backup Completed Successfully</h2>'
                . '<p><strong>Backup Name:</strong> ' . $filename . '</p>'
                . '<p><strong>Backup Size:</strong> ' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><strong>Date:</strong> ' . $date . '</p>'
                . '<p><strong>Google Drive Link:</strong> '
                . ($link !== 'N/A' ? '<a href="' . $link . '">' . $link . '</a>' : 'N/A') . '</p>'
                . '<p><strong>Status:</strong> ' . $status . '</p>';
        } else {
            $subject = 'Database Backup Failed';
            $err = htmlspecialchars((string) ($row['error_message'] ?? 'Unknown error'), ENT_QUOTES, 'UTF-8');
            $html = '<h2>Database Backup Failed</h2>'
                . '<p><strong>Backup Name:</strong> ' . $filename . '</p>'
                . '<p><strong>Date:</strong> ' . $date . '</p>'
                . '<p><strong>Status:</strong> ' . $status . '</p>'
                . '<p><strong>Error:</strong> ' . $err . '</p>';
        }

        try {
            $mailer->send($to, $name, $subject, $html);
        } catch (Throwable $e) {
            @error_log('[TMS Backup] email failed: ' . $e->getMessage());
        }
    }

    private function ensureDirectories(): void
    {
        foreach (['storage_path', 'progress_path', 'lock_path'] as $key) {
            $rel = (string) ($this->config[$key] ?? '');
            if ($rel === '') {
                continue;
            }
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
        }
        // Deny web access
        $htaccess = $this->getBackupDirectory() . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }
    }
}
