<?php
// app/core/Database.php
// One PDO connection for the whole request. Use: Database::get()->prepare(...)
// Settings come from config/database.php (or database.example.php when it is missing).
// Used by every model and by too_many() in helpers/functions.php.

class Database
{
    private static $pdo = null;

    // Connects on the first call, then gives back the same connection
    public static function get()
    {
        if (self::$pdo === null) {
            $file = ROOT_PATH . '/config/database.php';
            if (!is_file($file)) {
                $file = ROOT_PATH . '/config/database.example.php';
            }
            $config = require $file;
            $dsn = 'mysql:host=' . $config['host']
                 . ';port=' . (int)$config['port']
                 . ';dbname=' . $config['database']
                 . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errors become exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
            ]);
            self::$pdo->exec("SET time_zone = '+06:00'");                  // Bangladesh time, same as PHP (config.php)
        }
        return self::$pdo;
    }
}
