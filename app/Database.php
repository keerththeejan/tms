<?php
class Database
{
    private static ?PDO $pdo = null;
    private static array $config;

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $db = self::$config['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset']);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$pdo = new PDO($dsn, $db['username'], $db['password'], $options);
        return self::$pdo;
    }
}
