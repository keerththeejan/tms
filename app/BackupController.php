<?php

declare(strict_types=1);

/**
 * Enterprise Backup Management — HTTP entry for page=backup.
 * Keeps routing unchanged: public/index.php?page=backup&action=...
 */
class BackupController
{
    public static function dispatch(PDO $pdo): void
    {
        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        BackupSchemaRepository::ensureSchema($pdo);

        // Traffic fallback scheduler (non-blocking when already done today)
        try {
            BackupScheduler::tickFromWeb($pdo);
        } catch (Throwable $e) {
            @error_log('[TMS BackupScheduler] ' . $e->getMessage());
        }

        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'index');
        $isPost = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        // JSON API actions
        if (str_starts_with($action, 'api_') || in_array($action, [
            'create', 'backup_now', 'status', 'history', 'delete', 'restore', 'log', 'dashboard',
            'retry_upload', 'drive_status',
        ], true)) {
            // Map legacy create → backup_now
            if ($action === 'create') {
                $action = 'backup_now';
            }
            self::dispatchApi($pdo, $action, $isPost);
            return;
        }

        if ($action === 'download') {
            self::download($pdo);
            return;
        }

        if ($action === 'reset_data' && $isPost) {
            self::resetData($pdo);
            return;
        }

        // Default dashboard
        self::renderDashboard($pdo);
    }

    private static function dispatchApi(PDO $pdo, string $action, bool $isPost): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            switch ($action) {
                case 'dashboard':
                case 'api_dashboard':
                    $service = new BackupService($pdo);
                    self::json([
                        'ok' => true,
                        'stats' => $service->getDashboardStats(),
                        'history' => BackupRepository::listRecent($pdo, 50),
                    ]);
                    return;

                case 'drive_status':
                case 'api_drive_status':
                    $drive = new GoogleDriveService();
                    self::json([
                        'ok' => true,
                        'drive' => $drive->getStatusSummary(),
                        'diagnostics' => $drive->diagnose(),
                    ]);
                    return;

                case 'retry_upload':
                case 'api_retry_upload':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);
                        return;
                    }
                    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                        self::json(['ok' => false, 'error' => 'Invalid CSRF token'], 400);
                        return;
                    }
                    $service = new BackupService($pdo);
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $result = $service->retryUpload($id);
                    } else {
                        $count = $service->retryFailedUploads();
                        $result = [
                            'ok' => $count > 0,
                            'retried' => $count,
                            'error' => $count > 0 ? null : 'No pending uploads succeeded (check Drive configuration).',
                        ];
                    }
                    if (!empty($result['ok'])) {
                        self::json([
                            'ok' => true,
                            'message' => $id > 0
                                ? 'Backup uploaded to Google Drive.'
                                : 'Retried pending Google Drive uploads.',
                            'result' => $result,
                            'stats' => $service->getDashboardStats(),
                            'history' => BackupRepository::listRecent($pdo, 50),
                        ]);
                        return;
                    }
                    self::json([
                        'ok' => false,
                        'error' => $result['error'] ?? 'Retry failed',
                        'stats' => $service->getDashboardStats(),
                    ], 400);
                    return;

                case 'history':
                case 'api_history':
                    self::json([
                        'ok' => true,
                        'history' => BackupRepository::listRecent($pdo, 100),
                    ]);
                    return;

                case 'status':
                case 'api_status':
                    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
                    $row = $id > 0 ? BackupRepository::findById($pdo, $id) : null;
                    if ($row === null) {
                        self::json(['ok' => false, 'error' => 'Backup not found'], 404);
                        return;
                    }
                    self::json(['ok' => true, 'backup' => $row]);
                    return;

                case 'log':
                case 'api_log':
                    $id = (int) ($_GET['id'] ?? 0);
                    $row = $id > 0 ? BackupRepository::findById($pdo, $id) : null;
                    if ($row === null) {
                        self::json(['ok' => false, 'error' => 'Backup not found'], 404);
                        return;
                    }
                    $entries = [];
                    if (!empty($row['log_detail'])) {
                        $decoded = json_decode((string) $row['log_detail'], true);
                        if (is_array($decoded)) {
                            $entries = $decoded;
                        }
                    }
                    self::json(['ok' => true, 'backup' => $row, 'log' => $entries]);
                    return;

                case 'backup_now':
                case 'api_backup_now':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);
                        return;
                    }
                    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
                        self::json(['ok' => false, 'error' => 'Invalid CSRF token'], 400);
                        return;
                    }
                    $user = Auth::user() ?? [];
                    $service = new BackupService($pdo);
                    $result = $service->runBackup(
                        'MANUAL',
                        Auth::id(),
                        (string) ($user['name'] ?? $user['username'] ?? 'Administrator'),
                        self::clientIp()
                    );
                    if (!empty($result['ok'])) {
                        self::json([
                            'ok' => true,
                            'message' => 'Backup completed successfully.',
                            'result' => $result,
                            'stats' => $service->getDashboardStats(),
                            'history' => BackupRepository::listRecent($pdo, 50),
                        ]);
                        return;
                    }
                    self::json([
                        'ok' => false,
                        'error' => $result['error'] ?? 'Backup failed',
                        'result' => $result,
                    ], 500);
                    return;

                case 'delete':
                case 'api_delete':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);
                        return;
                    }
                    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                        self::json(['ok' => false, 'error' => 'Invalid CSRF token'], 400);
                        return;
                    }
                    $id = (int) ($_POST['id'] ?? 0);
                    $service = new BackupService($pdo);
                    $result = $service->deleteBackup($id, true);
                    if (!empty($result['ok'])) {
                        self::json([
                            'ok' => true,
                            'message' => 'Backup deleted from local storage, Google Drive, and logs.',
                            'stats' => $service->getDashboardStats(),
                            'history' => BackupRepository::listRecent($pdo, 50),
                        ]);
                        return;
                    }
                    self::json(['ok' => false, 'error' => $result['error'] ?? 'Delete failed'], 400);
                    return;

                case 'restore':
                case 'api_restore':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);
                        return;
                    }
                    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                        self::json(['ok' => false, 'error' => 'Invalid CSRF token'], 400);
                        return;
                    }
                    $confirm = trim((string) ($_POST['confirm'] ?? ''));
                    if ($confirm !== 'RESTORE') {
                        self::json(['ok' => false, 'error' => 'Type RESTORE to confirm.'], 400);
                        return;
                    }
                    $id = (int) ($_POST['id'] ?? 0);
                    $user = Auth::user() ?? [];
                    $restore = new RestoreService($pdo);
                    $result = $restore->restoreFromBackupId(
                        $id,
                        Auth::id(),
                        (string) ($user['name'] ?? $user['username'] ?? 'Administrator'),
                        self::clientIp()
                    );
                    if (!empty($result['ok'])) {
                        self::json([
                            'ok' => true,
                            'message' => $result['message'] ?? 'Restore completed.',
                            'restore_point_id' => $result['restore_point_id'] ?? null,
                        ]);
                        return;
                    }
                    self::json(['ok' => false, 'error' => $result['error'] ?? 'Restore failed'], 500);
                    return;

                default:
                    self::json(['ok' => false, 'error' => 'Unknown action'], 404);
            }
        } catch (Throwable $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private static function download(PDO $pdo): void
    {
        $file = basename((string) ($_GET['file'] ?? ''));
        $id = (int) ($_GET['id'] ?? 0);

        $path = null;
        $downloadName = $file;

        if ($id > 0) {
            $row = BackupRepository::findById($pdo, $id);
            if ($row === null) {
                http_response_code(404);
                echo 'Not found';
                return;
            }
            $service = new BackupService($pdo);
            $path = $service->resolveLocalPath($row);
            $downloadName = (string) ($row['filename'] ?? 'backup.zip');
        } else {
            // New ZIP format or legacy SQL
            $validNew = (bool) preg_match('/^TMS_Backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $file);
            $validLegacy = (bool) preg_match('/^tms_backup_\d{8}_\d{6}\.sql$/', $file);
            if (!$validNew && !$validLegacy) {
                http_response_code(400);
                echo 'Invalid file.';
                return;
            }
            $service = new BackupService($pdo);
            $path = $service->getBackupDirectory() . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path)) {
                http_response_code(404);
                echo 'Not found';
                return;
            }
        }

        if ($path === null || !is_file($path)) {
            http_response_code(404);
            echo 'File not found on disk.';
            return;
        }

        $mime = str_ends_with(strtolower($downloadName), '.zip') ? 'application/zip' : 'application/sql';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private static function renderDashboard(PDO $pdo): void
    {
        $service = new BackupService($pdo);
        $stats = $service->getDashboardStats();
        $history = BackupRepository::listRecent($pdo, 50);

        // Legacy loose files (pre-enterprise) not yet in DB
        $legacy = [];
        foreach (glob($service->getBackupDirectory() . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') ?: [] as $f) {
            $legacy[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'mtime' => filemtime($f),
            ];
        }
        usort($legacy, static fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        Helpers::view('backup/index', [
            'stats' => $stats,
            'history' => $history,
            'legacyFiles' => $legacy,
            'csrf' => Helpers::csrfToken(),
            'apiBase' => Helpers::baseUrl('index.php?page=backup'),
        ]);
    }

    /**
     * Preserved from original backup module — full database reset (admin only).
     */
    private static function resetData(PDO $pdo): void
    {
        $wantsJson = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        );

        if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
                return;
            }
            http_response_code(400);
            echo 'Invalid CSRF';
            return;
        }

        $confirm = trim((string) ($_POST['confirm_reset'] ?? ''));
        if ($confirm !== 'DELETE') {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Type DELETE (all caps) to confirm reset.']);
                return;
            }
            self::renderDashboard($pdo);
            return;
        }

        $result = DataReset::performFullDatabaseReset($pdo);
        if ($result['success']) {
            Auth::logout();
            if ($wantsJson) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Database reset completed successfully.',
                    'cleared' => $result['cleared'],
                    'tables_found' => $result['tables_found'],
                    'phases' => $result['phases'],
                    'redirect' => Helpers::baseUrl('index.php?page=login&reset=1'),
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            Helpers::redirect('index.php?page=login&reset=1');
            return;
        }

        $errorMessages = array_merge(
            $result['errors'],
            array_map(
                static fn ($table, $count) => "Table {$table} still has {$count} row(s)",
                array_keys($result['verification_failures']),
                $result['verification_failures']
            ),
            array_map(
                static fn ($table, $count) => "Accounting table {$table} still has {$count} row(s)",
                array_keys($result['accounting_failures']),
                $result['accounting_failures']
            )
        );

        if ($wantsJson) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database reset failed. Some records could not be removed.',
                'errors' => $errorMessages,
                'verification_failures' => $result['verification_failures'],
                'accounting_failures' => $result['accounting_failures'],
                'cleared' => $result['cleared'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        self::renderDashboard($pdo);
    }

    private static function json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private static function clientIp(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }
}
