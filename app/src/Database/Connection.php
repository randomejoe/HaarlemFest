<?php

namespace App\Database;

use App\Config;
use PDO;
use PDOException;

class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Config::env('DB_HOST', 'mysql');
        $db = Config::env('DB_NAME', 'festival');
        $user = Config::env('DB_USER', 'root');
        $pass = Config::env('DB_PASS', 'secret123');
        $port = Config::envInt('DB_PORT', 3306);

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed.');
        }

        return self::$pdo;
    }
}
