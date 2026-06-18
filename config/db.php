<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'orders_db');

// Auto-detect subdirectory prefix (e.g. '' locally, '/Orders' on production)
$_appDir  = str_replace('\\', '/', dirname(__DIR__));
$_docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
define('APP_BASE', $_docRoot !== '' ? rtrim(str_replace($_docRoot, '', $_appDir), '/') : '');
unset($_appDir, $_docRoot);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
