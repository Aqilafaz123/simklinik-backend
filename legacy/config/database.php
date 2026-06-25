<?php

/**
 * Koneksi PDO ke MariaDB/MySQL.
 * Saat di-load via Laravel proxy, pakai config database Laravel (.env).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        if (defined('SIM_LEGACY_PROXY') && function_exists('config')) {
            $cfg = config('database.connections.' . config('database.default'));
            $host = $cfg['host'];
            $port = $cfg['port'];
            $name = $cfg['database'];
            $user = $cfg['username'];
            $pass = $cfg['password'] ?? '';
        } else {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
            $name = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'sim_klinik';
            $user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
            $pass = array_key_exists('DB_PASSWORD', $_ENV)
                ? $_ENV['DB_PASSWORD']
                : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}
