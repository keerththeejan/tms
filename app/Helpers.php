<?php
class Helpers
{
    public static function config(): array
    {
        static $config;
        if (!$config) {
            $config = require __DIR__ . '/../config/config.php';
        }
        return $config;
    }

    public static function company(): array
    {
        $cfg = self::config();
        return is_array($cfg['company'] ?? null) ? $cfg['company'] : [];
    }

    public static function companyBranches(): array
    {
        $company = self::company();
        $branches = $company['branches'] ?? [];
        return is_array($branches) ? $branches : [];
    }

    public static function companyDefaultBranch(): array
    {
        $branches = self::companyBranches();
        foreach ($branches as $b) {
            if (!is_array($b)) { continue; }
            $name = trim((string)($b['name'] ?? ''));
            $addrTa = trim((string)($b['address_ta'] ?? ''));
            $addrEn = trim((string)($b['address_en'] ?? ''));
            $phones = trim((string)($b['phones'] ?? ''));
            if ($name !== '' || $addrTa !== '' || $addrEn !== '' || $phones !== '') {
                return $b;
            }
        }
        return [];
    }

    public static function companyHeaderAddressLines(?string $addrOverrideParam = null, int $limit = 3): array
    {
        $addrOverrideParam = (string)($addrOverrideParam ?? '');
        $addrOverrideParam = str_replace(["\r"], '', $addrOverrideParam);

        $lines = [];
        if (trim($addrOverrideParam) !== '') {
            $tmp = explode("\n", $addrOverrideParam);
            foreach ($tmp as $a) {
                $a = trim((string)$a);
                if ($a !== '') { $lines[] = $a; }
            }
            return array_slice($lines, 0, max(0, $limit));
        }

        $branches = self::companyBranches();
        if (!empty($branches)) {
            foreach ($branches as $b) {
                if (!is_array($b)) { continue; }
                $name = trim((string)($b['name'] ?? ''));
                $addrEn = trim((string)($b['address_en'] ?? ''));
                $phones = trim((string)($b['phones'] ?? ''));
                $line = trim(($name !== '' ? ($name . ': ') : '') . $addrEn);
                if ($phones !== '') {
                    $line = trim($line . ' | ' . $phones);
                }
                if ($line !== '') { $lines[] = $line; }
            }
            if (!empty($lines)) {
                return array_slice($lines, 0, max(0, $limit));
            }
        }

        $company = self::company();
        $legacy = $company['addresses'] ?? [];
        if (is_array($legacy)) {
            foreach ($legacy as $a) {
                $a = trim((string)$a);
                if ($a !== '') { $lines[] = $a; }
            }
        }

        return array_slice($lines, 0, max(0, $limit));
    }

    public static function baseUrl(string $path = ''): string
    {
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $base = rtrim($scriptName, '/\\');
        return $base . '/' . ltrim($path, '/');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::baseUrl($path));
        exit;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }

    public static function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }
        include __DIR__ . '/../views/layout/header.php';
        include $viewFile;
        include __DIR__ . '/../views/layout/footer.php';
    }
}
