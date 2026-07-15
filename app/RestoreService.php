<?php

declare(strict_types=1);

/**
 * Database restore from a TMS backup ZIP (or legacy .sql).
 * Always creates a restore-point backup before applying.
 */
class RestoreService
{
    private PDO $pdo;
    private BackupService $backupService;

    public function __construct(PDO $pdo, ?BackupService $backupService = null)
    {
        $this->pdo = $pdo;
        $this->backupService = $backupService ?? new BackupService($pdo);
    }

    /**
     * @return array{ok:bool, message?:string, restore_point_id?:int, error?:string}
     */
    public function restoreFromBackupId(int $backupId, ?int $userId = null, ?string $userName = null, ?string $ip = null): array
    {
        $row = BackupRepository::findById($this->pdo, $backupId);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Backup record not found.'];
        }

        $path = $this->backupService->resolveLocalPath($row);
        if ($path === null) {
            return ['ok' => false, 'error' => 'Backup file is missing from local storage.'];
        }

        return $this->restoreFromFile($path, $userId, $userName, $ip);
    }

    /**
     * @return array{ok:bool, message?:string, restore_point_id?:int, error?:string}
     */
    public function restoreFromFile(string $path, ?int $userId = null, ?string $userName = null, ?string $ip = null): array
    {
        if (!is_file($path)) {
            return ['ok' => false, 'error' => 'Restore file does not exist.'];
        }

        // Safety: create restore point first
        $rp = $this->backupService->runBackup('RESTORE_POINT', $userId, $userName, $ip);
        if (empty($rp['ok'])) {
            return [
                'ok' => false,
                'error' => 'Could not create restore point before restore: ' . ($rp['error'] ?? 'unknown'),
            ];
        }

        $sqlFile = null;
        $cleanup = [];
        try {
            $lower = strtolower($path);
            if (str_ends_with($lower, '.zip')) {
                $sqlFile = $this->extractSqlFromZip($path);
                $cleanup[] = $sqlFile;
            } elseif (str_ends_with($lower, '.sql')) {
                $sqlFile = $path;
            } else {
                throw new RuntimeException('Unsupported backup format. Expected .zip or .sql.');
            }

            $this->importSqlFile($sqlFile);

            return [
                'ok' => true,
                'message' => 'Database restored successfully. A restore-point backup was created first.',
                'restore_point_id' => (int) ($rp['backup_id'] ?? 0),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'restore_point_id' => (int) ($rp['backup_id'] ?? 0),
            ];
        } finally {
            foreach ($cleanup as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
    }

    private function extractSqlFromZip(string $zipPath): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP ZipArchive extension is required to restore ZIP backups.');
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open backup ZIP.');
        }

        $sqlEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && str_ends_with(strtolower($name), '.sql')) {
                $sqlEntry = $name;
                break;
            }
        }
        if ($sqlEntry === null) {
            $zip->close();
            throw new RuntimeException('No SQL dump found inside the backup ZIP.');
        }

        $tmpDir = $this->backupService->getBackupDirectory() . DIRECTORY_SEPARATOR . '.restore_tmp';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }
        $tmpSql = $tmpDir . DIRECTORY_SEPARATOR . 'restore_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.sql';

        $stream = $zip->getStream($sqlEntry);
        if ($stream === false) {
            $zip->close();
            throw new RuntimeException('Unable to read SQL entry from ZIP.');
        }
        $out = fopen($tmpSql, 'wb');
        if ($out === false) {
            fclose($stream);
            $zip->close();
            throw new RuntimeException('Unable to write temporary SQL file.');
        }
        while (!feof($stream)) {
            $chunk = fread($stream, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            fwrite($out, $chunk);
        }
        fclose($out);
        fclose($stream);
        $zip->close();

        if (!is_file($tmpSql) || filesize($tmpSql) <= 0) {
            throw new RuntimeException('Extracted SQL dump is empty.');
        }

        return $tmpSql;
    }

    private function importSqlFile(string $sqlFile): void
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

        $mysql = $this->resolveMysqlClient($appConfig);
        $cmd = '"' . $mysql . '"'
            . ' --host=' . escapeshellarg($host)
            . ' --port=' . escapeshellarg((string) $port)
            . ' --user=' . escapeshellarg($user)
            . ' ' . escapeshellarg($dbname)
            . ' < ' . escapeshellarg($sqlFile);

        putenv('MYSQL_PWD=' . $pass);
        $output = [];
        $ret = 0;
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('cmd /C ' . escapeshellarg($cmd) . ' 2>&1', $output, $ret);
        } else {
            @exec($cmd . ' 2>&1', $output, $ret);
        }
        putenv('MYSQL_PWD');

        if ($ret !== 0) {
            // Fallback: PDO multi-statement import for smaller dumps
            $this->importViaPdo($sqlFile);
        }
    }

    private function importViaPdo(string $sqlFile): void
    {
        $sql = file_get_contents($sqlFile);
        if ($sql === false || $sql === '') {
            throw new RuntimeException('Unable to read SQL dump for restore.');
        }

        // Strip mysqldump noise that can break PDO multi-query
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try {
            // Execute in chunks on semicolons outside strings is complex;
            // prefer mysqli multi_query when available.
            if (function_exists('mysqli_connect')) {
                $this->importViaMysqli($sql);
            } else {
                foreach ($this->splitSqlStatements($sql) as $statement) {
                    $statement = trim($statement);
                    if ($statement === '') {
                        continue;
                    }
                    $this->pdo->exec($statement);
                }
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function importViaMysqli(string $sql): void
    {
        $appConfig = Helpers::config();
        $db = $appConfig['db'] ?? [];
        $mysqli = @new mysqli(
            (string) ($db['host'] ?? '127.0.0.1'),
            (string) ($db['username'] ?? 'root'),
            (string) ($db['password'] ?? ''),
            (string) ($db['database'] ?? ''),
            (int) ($db['port'] ?? 3306)
        );
        if ($mysqli->connect_errno) {
            throw new RuntimeException('mysqli connect failed: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
        $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
        if (!$mysqli->multi_query($sql)) {
            $err = $mysqli->error;
            $mysqli->close();
            throw new RuntimeException('Restore import failed: ' . $err);
        }
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());

        if ($mysqli->errno) {
            $err = $mysqli->error;
            $mysqli->close();
            throw new RuntimeException('Restore import failed: ' . $err);
        }
        $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
        $mysqli->close();
    }

    /** @return list<string> */
    private function splitSqlStatements(string $sql): array
    {
        $parts = preg_split('/;\s*\n/', $sql) ?: [];
        return array_values(array_filter(array_map('trim', $parts)));
    }

    private function resolveMysqlClient(array $appConfig): string
    {
        $backupCfg = $this->backupService->getConfig();
        $configured = trim((string) ($backupCfg['mysql_path'] ?? ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (glob('C:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysql.exe') ?: [] as $p) {
                if (is_file($p)) {
                    return $p;
                }
            }
            return 'mysql.exe';
        }
        foreach (['/usr/bin/mysql', '/usr/local/bin/mysql', 'mysql'] as $p) {
            if ($p === 'mysql' || is_file($p)) {
                return $p;
            }
        }

        return 'mysql';
    }
}
