<?php
require_once __DIR__ . '/../../api/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') method_not_allowed(['GET']);

$auth = require_auth();

$stmt = $pdo->prepare("SELECT id, name, email, company, role, created_at FROM users WHERE id = ?");
$stmt->execute([$auth['sub']]);
$user = $stmt->fetch();

if (!$user) error_response('მომხმარებელი ვერ მოიძებნა.', 404);

json_response($user);
