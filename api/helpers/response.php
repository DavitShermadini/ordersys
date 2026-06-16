<?php
function json_response($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function error_response(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
}

function method_not_allowed(array $allowed = []): void {
    if ($allowed) header('Allow: ' . implode(', ', $allowed));
    error_response('Method not allowed', 405);
}
