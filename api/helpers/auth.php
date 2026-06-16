<?php
function get_bearer_token(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? apache_request_headers()['Authorization']
           ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return $m[1];
    return null;
}

function get_auth_user(): ?array {
    $token = get_bearer_token();
    if (!$token) return null;
    return jwt_decode($token);
}

function require_auth(): array {
    $user = get_auth_user();
    if (!$user) error_response('Unauthenticated', 401);
    return $user;
}

function require_admin(): array {
    $user = require_auth();
    if (($user['role'] ?? '') !== 'admin') error_response('Forbidden', 403);
    return $user;
}
