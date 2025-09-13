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
