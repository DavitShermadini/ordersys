<?php
// CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers/jwt.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/auth.php';

// JSON body helper
function body(): array {
    static $parsed = null;
    if ($parsed === null) {
        $parsed = json_decode(file_get_contents('php://input'), true) ?? [];
    }
    return $parsed;
}
