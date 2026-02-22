<?php
/**
 * Database connection singleton using PDO
 */

// Load .env from project root
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    $envVars = parse_ini_file($envPath);
    foreach ($envVars as $key => $value) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'graduation';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'grad_user';
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
