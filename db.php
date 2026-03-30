<?php
// db.php
// Use this in every page that needs DB access.

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'amd_login');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set strong password for production

define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

function getPDO() {
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}
