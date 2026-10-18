<?php
// app/core/Database.php
// One PDO connection for the whole request. Use: Database::get()->prepare(...)
// Settings come from config/database.php.

class Database
{
    private static $pdo = null;

    public static function get()
    {
        if (self::$pdo === null) {
            $file = ROOT_PATH . '/config/database.php';
            if (!is_file($file)) {
                $file = ROOT_PATH . '/config/database.example.php';
            }
            $c = require $file;
            $dsn = 'mysql:host=' . $c['host'] . ';port=' . (int)$c['port'] . ';dbname=' . $c['database'] . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, $c['username'], $c['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errors become exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
            ]);
            self::$pdo->exec("SET time_zone = '+06:00'");
        }
        return self::$pdo;
    }
}
