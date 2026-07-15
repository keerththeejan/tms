<?php

declare(strict_types=1);

/**
 * Google Drive API v3 — prefers google/apiclient; JWT+cURL fallback if SDK missing.
 * Auth: Service Account JSON (server-side automation).
 */
class GoogleDriveService
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const DRIVE_FILES = 'https://www.googleapis.com/drive/v3/files';
    private const UPLOAD_URI = 'https://www.googleapis.com/upload/drive/v3/files';
    private const DEFAULT_SCOPE = 'https://www.googleapis.com/auth/drive';

    private array $config;
    private ?array $credentials = null;
    private ?string $accessToken = null;
    private string $lastError = '';
    private ?\Google\Client $googleClient = null;
    private ?\Google\Service\Drive $driveService = null;

    public function __construct(?array $backupConfig = null)
    {
        $this->config = self::resolveDriveConfig($backupConfig);
    }

    /**
     * Merge backup.php google_drive + config/google-drive.php (latter wins).
     *
     * @param array<string, mixed>|null $backupConfig
     * @return array<string, mixed>
     */
    public static function resolveDriveConfig(?array $backupConfig = null): array
    {
        $base = [];
        $full = $backupConfig ?? self::loadConfig();
        if (isset($full['google_drive']) && is_array($full['google_drive'])) {
            $base = $full['google_drive'];
        }

        $dedicated = dirname(__DIR__) . '/config/google-drive.php';
        if (is_file($dedicated)) {
            $g = require $dedicated;
            if (is_array($g)) {
                $base = array_merge($base, $g);
            }
        }

        return $base;
    }

    public static function loadConfig(): array
    {
        $path = dirname(__DIR__) . '/config/backup.php';
        if (!is_file($path)) {
            return [];
        }
        $cfg = require $path;

        return is_array($cfg) ? $cfg : [];
    }

    public function isEnabled(): bool
    {
        return !empty($this->config['enabled']);
    }

    public function isConfigured(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $path = $this->resolveCredentialsPath();
        if ($path === null || !is_file($path) || !is_readable($path)) {
            return false;
        }

        $creds = $this->loadCredentials();

        return $creds !== null
            && !empty($creds['client_email'])
            && !empty($creds['private_key'])
            && str_contains((string) $creds['private_key'], 'BEGIN PRIVATE KEY')
            && !str_contains((string) $creds['private_key'], 'REPLACE_ME');
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Full diagnostic checklist for the Backup page / CLI.
     *
     * @return array<string, mixed>
     */
    public function diagnose(): array
    {
        $credPath = $this->resolveCredentialsPath();
        $credExists = $credPath !== null && is_file($credPath);
        $credReadable = $credExists && is_readable((string) $credPath);
        $sdkInstalled = class_exists(\Google\Client::class);
        $checks = [
            'google_drive_enabled' => $this->isEnabled(),
            'google_apiclient_installed' => $sdkInstalled,
            'credentials_path' => $credPath,
            'credentials_file_exists' => $credExists,
            'credentials_file_readable' => $credReadable,
            'credentials_json_valid' => false,
            'service_account_email' => null,
            'folder_id_configured' => trim((string) ($this->config['folder_id'] ?? '')) !== '',
            'folder_id' => trim((string) ($this->config['folder_id'] ?? '')),
            'folder_name' => (string) ($this->config['folder_name'] ?? 'TMS Database Backups'),
            'auth_ok' => false,
            'folder_ok' => false,
            'client_ok' => false,
            'connected' => false,
            'blocking_reason' => null,
            'errors' => [],
        ];

        if (!$this->isEnabled()) {
            $checks['blocking_reason'] = 'Google Drive is disabled in config/google-drive.php (enabled = false).';

            return $checks;
        }

        if (!$credExists) {
            $checks['blocking_reason'] = 'Service account JSON missing. Place your key at config/google-drive-service-account.json '
                . '(copy from google-drive-service-account.example.json after downloading a real key from Google Cloud).';
            $checks['errors'][] = $checks['blocking_reason'];

            return $checks;
        }

        if (!$credReadable) {
            $checks['blocking_reason'] = 'Service account JSON exists but is not readable by PHP.';
            $checks['errors'][] = $checks['blocking_reason'];

            return $checks;
        }

        $creds = $this->loadCredentials();
        if ($creds === null) {
            $checks['blocking_reason'] = 'Service account JSON is invalid or incomplete.';
            $checks['errors'][] = $checks['blocking_reason'];

            return $checks;
        }

        $checks['credentials_json_valid'] = true;
        $checks['service_account_email'] = (string) ($creds['client_email'] ?? '');

        if (!$this->isConfigured()) {
            $checks['blocking_reason'] = 'Credentials look like a placeholder. Replace REPLACE_ME with a real Google Cloud service-account key.';
            $checks['errors'][] = $checks['blocking_reason'];

            return $checks;
        }

        try {
            $this->getClient();
            $checks['client_ok'] = true;
            $this->authenticate();
            $checks['auth_ok'] = true;
            $folderId = $this->ensureBackupFolder();
            $checks['folder_ok'] = $folderId !== '';
            $checks['folder_id'] = $folderId;
            $checks['connected'] = true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            $checks['errors'][] = $e->getMessage();
            $checks['blocking_reason'] = $e->getMessage();
        }

        return $checks;
    }

    public function getStatusSummary(): array
    {
        $diag = $this->diagnose();

        return [
            'configured' => $this->isConfigured(),
            'enabled' => $this->isEnabled(),
            'connected' => !empty($diag['connected']),
            'status' => !empty($diag['connected'])
                ? 'Connected'
                : ($this->isEnabled() ? 'Not ready' : 'Disabled'),
            'folder' => (string) ($diag['folder_name'] ?? 'TMS Database Backups'),
            'folder_id' => (string) ($diag['folder_id'] ?? ''),
            'service_account' => $diag['service_account_email'] ?? null,
            'credentials_path' => $diag['credentials_path'] ?? null,
            'sdk' => !empty($diag['google_apiclient_installed']) ? 'google/apiclient' : 'jwt+curl',
            'error' => $diag['blocking_reason'] ?? null,
            'diagnostics' => $diag,
        ];
    }

    /**
     * Human-readable reason when upload cannot proceed (enabled but not ready).
     */
    public function getConfigurationError(): string
    {
        $diag = $this->diagnose();

        return (string) ($diag['blocking_reason'] ?? 'Google Drive is not ready.');
    }

    /**
     * @return array{file_id: string, web_view_link: string}
     */
    public function uploadFile(string $localPath, string $remoteName, ?string $mimeType = 'application/zip'): array
    {
        if (!is_file($localPath)) {
            throw new RuntimeException('Local backup file not found for upload.');
        }
        if (!$this->isEnabled()) {
            throw new RuntimeException('Google Drive upload is disabled in configuration.');
        }
        if (!$this->isConfigured()) {
            throw new RuntimeException($this->getConfigurationError());
        }

        $folderId = $this->ensureBackupFolder();
        $timeout = (int) ($this->config['upload_timeout'] ?? 300);

        if (class_exists(\Google\Client::class)) {
            return $this->uploadWithSdk($localPath, $remoteName, $folderId, $mimeType ?? 'application/zip', $timeout);
        }

        return $this->uploadWithCurl($localPath, $remoteName, $folderId, $mimeType ?? 'application/zip', $timeout);
    }

    public function deleteFile(string $fileId): bool
    {
        if ($fileId === '') {
            return true;
        }
        try {
            if (class_exists(\Google\Client::class)) {
                $drive = $this->getDriveService();
                $drive->files->delete($fileId, ['supportsAllDrives' => true]);

                return true;
            }
            $this->authenticate();
            $response = $this->httpRequest(
                'DELETE',
                self::DRIVE_FILES . '/' . rawurlencode($fileId) . '?supportsAllDrives=true',
                null,
                ['Authorization: Bearer ' . $this->accessToken],
                (int) ($this->config['upload_timeout'] ?? 300)
            );
            $code = (int) ($response['status'] ?? 0);

            return $code === 204 || $code === 200 || $code === 404;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }

    public function ensureBackupFolder(): string
    {
        $configured = trim((string) ($this->config['folder_id'] ?? ''));
        if ($configured === '') {
            $configured = $this->resolveCachedFolderId();
        }
        if ($configured !== '') {
            $this->verifyFolderAccess($configured);
            $this->persistFolderId($configured);

            return $configured;
        }

        $folderName = (string) ($this->config['folder_name'] ?? 'TMS Database Backups');

        if (class_exists(\Google\Client::class)) {
            $drive = $this->getDriveService();
            $safeName = str_replace("'", "\\'", $folderName);
            $q = "name = '{$safeName}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $list = $drive->files->listFiles([
                'q' => $q,
                'spaces' => 'drive',
                'fields' => 'files(id,name)',
                'pageSize' => 5,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);
            $files = $list->getFiles();
            if (!empty($files[0])) {
                $folderId = (string) $files[0]->getId();
                $this->persistFolderId($folderId);

                return $folderId;
            }

            $meta = new \Google\Service\Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);
            $created = $drive->files->create($meta, [
                'fields' => 'id,name',
                'supportsAllDrives' => true,
            ]);
            $folderId = (string) $created->getId();
            if ($folderId === '') {
                throw new RuntimeException('Unable to create Google Drive folder via API.');
            }
            $this->persistFolderId($folderId);

            return $folderId;
        }

        $this->authenticate();
        $q = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            str_replace("'", "\\'", $folderName)
        );
        $url = self::DRIVE_FILES . '?q=' . rawurlencode($q)
            . '&spaces=drive&fields=files(id,name)&pageSize=5&supportsAllDrives=true&includeItemsFromAllDrives=true';
        $response = $this->httpRequest('GET', $url, null, [
            'Authorization: Bearer ' . $this->accessToken,
        ], (int) ($this->config['upload_timeout'] ?? 300));
        $data = json_decode((string) ($response['body'] ?? ''), true);
        if (($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300
            && !empty($data['files'][0]['id'])) {
            $folderId = (string) $data['files'][0]['id'];
            $this->persistFolderId($folderId);

            return $folderId;
        }

        $createBody = json_encode([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ], JSON_UNESCAPED_UNICODE);
        $create = $this->httpRequest('POST', self::DRIVE_FILES . '?fields=id,name&supportsAllDrives=true', $createBody, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json; charset=UTF-8',
        ], (int) ($this->config['upload_timeout'] ?? 300));
        $created = json_decode((string) ($create['body'] ?? ''), true);
        if (empty($created['id'])) {
            throw new RuntimeException(
                'Unable to create Google Drive folder: HTTP '
                . ($create['status'] ?? 0) . ' ' . substr((string) ($create['body'] ?? ''), 0, 300)
                . '. Tip: create the folder in your Google Drive, share it with the service account as Editor, '
                . 'and set folder_id in config/google-drive.php.'
            );
        }

        $folderId = (string) $created['id'];
        $this->persistFolderId($folderId);

        return $folderId;
    }

    private function verifyFolderAccess(string $folderId): void
    {
        try {
            if (class_exists(\Google\Client::class)) {
                $drive = $this->getDriveService();
                $drive->files->get($folderId, [
                    'fields' => 'id,name,mimeType',
                    'supportsAllDrives' => true,
                ]);

                return;
            }
            $this->authenticate();
            $response = $this->httpRequest(
                'GET',
                self::DRIVE_FILES . '/' . rawurlencode($folderId) . '?fields=id,name,mimeType&supportsAllDrives=true',
                null,
                ['Authorization: Bearer ' . $this->accessToken],
                (int) ($this->config['upload_timeout'] ?? 300)
            );
            if (($response['status'] ?? 0) >= 400) {
                throw new RuntimeException(
                    'Cannot access Google Drive folder ID ' . $folderId . '. '
                    . 'Share the folder with the service account email as Editor. '
                    . substr((string) ($response['body'] ?? ''), 0, 300)
                );
            }
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Folder ID configured but not accessible: ' . $e->getMessage()
                . ' Share "TMS Database Backups" with the service account as Editor.',
                0,
                $e
            );
        }
    }

    /**
     * @return array{file_id: string, web_view_link: string}
     */
    private function uploadWithSdk(
        string $localPath,
        string $remoteName,
        string $folderId,
        string $mimeType,
        int $timeout
    ): array {
        $client = $this->getClient();
        // Guzzle timeout
        $client->setHttpClient(new \GuzzleHttp\Client(['timeout' => $timeout]));

        $drive = new \Google\Service\Drive($client);
        $fileMeta = new \Google\Service\Drive\DriveFile([
            'name' => $remoteName,
            'parents' => [$folderId],
        ]);

        $created = $drive->files->create($fileMeta, [
            'data' => file_get_contents($localPath),
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id,webViewLink,name,size',
            'supportsAllDrives' => true,
        ]);

        $fileId = (string) $created->getId();
        if ($fileId === '') {
            throw new RuntimeException('Google Drive SDK upload returned no file id.');
        }
        $link = (string) ($created->getWebViewLink() ?: ('https://drive.google.com/file/d/' . $fileId . '/view'));

        return ['file_id' => $fileId, 'web_view_link' => $link];
    }

    /**
     * @return array{file_id: string, web_view_link: string}
     */
    private function uploadWithCurl(
        string $localPath,
        string $remoteName,
        string $folderId,
        string $mimeType,
        int $timeout
    ): array {
        $this->authenticate();

        $metadata = [
            'name' => $remoteName,
            'parents' => [$folderId],
        ];

        $boundary = '=======TMS_BACKUP_' . bin2hex(random_bytes(8));
        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($metadata, JSON_UNESCAPED_UNICODE) . "\r\n"
            . "--{$boundary}\r\n"
            . 'Content-Type: ' . $mimeType . "\r\n\r\n"
            . (string) file_get_contents($localPath) . "\r\n"
            . "--{$boundary}--";

        $url = self::UPLOAD_URI . '?uploadType=multipart&fields=id,webViewLink,name,size&supportsAllDrives=true';
        $response = $this->httpRequest('POST', $url, $body, [
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Authorization: Bearer ' . $this->accessToken,
        ], $timeout);

        if (($response['status'] ?? 0) < 200 || ($response['status'] ?? 0) >= 300) {
            throw new RuntimeException(
                'Google Drive upload failed: HTTP ' . ($response['status'] ?? 0)
                . ' ' . substr((string) ($response['body'] ?? ''), 0, 400)
            );
        }

        $data = json_decode((string) ($response['body'] ?? ''), true);
        if (!is_array($data) || empty($data['id'])) {
            throw new RuntimeException('Google Drive upload returned an unexpected response.');
        }

        $fileId = (string) $data['id'];
        $link = (string) ($data['webViewLink'] ?? ('https://drive.google.com/file/d/' . $fileId . '/view'));

        return ['file_id' => $fileId, 'web_view_link' => $link];
    }

    private function getClient(): \Google\Client
    {
        if ($this->googleClient instanceof \Google\Client) {
            return $this->googleClient;
        }
        if (!class_exists(\Google\Client::class)) {
            throw new RuntimeException('google/apiclient is not installed. Run: composer require google/apiclient');
        }

        $path = $this->resolveCredentialsPath();
        if ($path === null) {
            throw new RuntimeException('Service account credentials path not found.');
        }

        $scopes = $this->config['scopes'] ?? [self::DEFAULT_SCOPE];
        if (!is_array($scopes) || $scopes === []) {
            $scopes = [self::DEFAULT_SCOPE];
        }

        $client = new \Google\Client();
        $client->setApplicationName('TMS Backup');
        $client->setAuthConfig($path);
        $client->setScopes($scopes);
        $client->setAccessType('offline');

        $this->googleClient = $client;

        return $this->googleClient;
    }

    private function getDriveService(): \Google\Service\Drive
    {
        if ($this->driveService instanceof \Google\Service\Drive) {
            return $this->driveService;
        }
        $this->driveService = new \Google\Service\Drive($this->getClient());

        return $this->driveService;
    }

    private function authenticate(): void
    {
        if ($this->accessToken !== null) {
            return;
        }

        if (class_exists(\Google\Client::class)) {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAssertion();
            if (!empty($token['error'])) {
                throw new RuntimeException(
                    'Google auth failed: ' . ($token['error_description'] ?? $token['error'])
                );
            }
            if (empty($token['access_token'])) {
                throw new RuntimeException('Google auth failed: no access_token returned.');
            }
            $this->accessToken = (string) $token['access_token'];

            return;
        }

        $creds = $this->loadCredentials();
        if ($creds === null) {
            throw new RuntimeException(
                'Google Drive service account credentials not found. '
                . 'Place JSON at config/google-drive-service-account.json'
            );
        }

        $scope = self::DEFAULT_SCOPE;
        if (!empty($this->config['scopes'][0])) {
            $scope = implode(' ', (array) $this->config['scopes']);
        }

        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => $scope,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $jwtHeader . '.' . $jwtClaim;
        $privateKey = openssl_pkey_get_private((string) $creds['private_key']);
        if ($privateKey === false) {
            throw new RuntimeException('Invalid Google Drive service account private key.');
        }
        $signature = '';
        if (!openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign Google Drive JWT.');
        }
        $jwt = $unsigned . '.' . $this->base64UrlEncode($signature);

        $post = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);
        $response = $this->httpRequest('POST', self::TOKEN_URI, $post, [
            'Content-Type: application/x-www-form-urlencoded',
        ], (int) ($this->config['upload_timeout'] ?? 300));
        $token = json_decode((string) ($response['body'] ?? ''), true);
        if (empty($token['access_token'])) {
            throw new RuntimeException(
                'Google Drive OAuth token request failed: '
                . substr((string) ($response['body'] ?? ''), 0, 400)
            );
        }

        $this->accessToken = (string) $token['access_token'];
    }

    private function loadCredentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = $this->resolveCredentialsPath();
        if ($path === null || !is_file($path)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        if (!is_array($raw)) {
            return null;
        }
        $this->credentials = $raw;

        return $this->credentials;
    }

    public function resolveCredentialsPath(): ?string
    {
        $candidates = [];
        $env = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        if (is_string($env) && $env !== '') {
            $candidates[] = $env;
        }
        $configured = trim((string) ($this->config['credentials_path'] ?? ''));
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        $candidates[] = 'config/google-drive-service-account.json';

        foreach ($candidates as $rel) {
            $path = $rel;
            if (!$this->isAbsolutePath($rel)) {
                $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
            }
            if (is_file($path)) {
                return $path;
            }
        }

        // Return expected default path for diagnostics even if missing
        $default = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'google-drive-service-account.json';

        return $default;
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('#^([A-Za-z]:[\\\\/]|/|\\\\)#', $path);
    }

    private function persistFolderId(string $folderId): void
    {
        $this->config['folder_id'] = $folderId;
        $cache = dirname(__DIR__) . '/storage/backups/.gdrive_folder_id';
        $dir = dirname($cache);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($cache, $folderId);

        // Persist into config/google-drive.php if folder_id empty and file writable
        $cfgPath = dirname(__DIR__) . '/config/google-drive.php';
        if (is_file($cfgPath) && is_writable($cfgPath)) {
            $contents = (string) file_get_contents($cfgPath);
            if (preg_match("/'folder_id'\\s*=>\\s*getenv\\('TMS_GDRIVE_FOLDER_ID'\\)\\s*\\?:\\s*''/", $contents)
                || preg_match("/'folder_id'\\s*=>\\s*''/", $contents)) {
                $updated = preg_replace(
                    "/'folder_id'\\s*=>\\s*(getenv\\('TMS_GDRIVE_FOLDER_ID'\\)\\s*\\?:\\s*)?''/",
                    "'folder_id' => getenv('TMS_GDRIVE_FOLDER_ID') ?: '" . addslashes($folderId) . "'",
                    $contents,
                    1
                );
                if (is_string($updated) && $updated !== $contents) {
                    @file_put_contents($cfgPath, $updated);
                }
            }
        }
    }

    private function resolveCachedFolderId(): string
    {
        $cached = trim((string) ($this->config['folder_id'] ?? ''));
        if ($cached !== '') {
            return $cached;
        }
        $cache = dirname(__DIR__) . '/storage/backups/.gdrive_folder_id';
        if (is_file($cache)) {
            $id = trim((string) file_get_contents($cache));
            if ($id !== '') {
                $this->config['folder_id'] = $id;

                return $id;
            }
        }

        return '';
    }

    /** @return array{status:int, body:string} */
    private function httpRequest(string $method, string $url, ?string $body, array $headers, int $timeout = 300): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for Google Drive uploads.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => max(30, $timeout),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($responseBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL error talking to Google: ' . $err);
        }
        curl_close($ch);

        return ['status' => $status, 'body' => (string) $responseBody];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
