<?php
require_once __DIR__ . '/../../api/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') method_not_allowed(['POST']);

$b        = body();
$email    = trim($b['email'] ?? '');
$password = $b['password'] ?? '';

if (!$email || !$password) error_response('ელ-ფოსტა და პაროლი სავალდებულოა.');

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    error_response('არასწორი ელ-ფოსტა ან პაროლი.', 401);
}

$token = jwt_encode([
    'sub'     => $user['id'],
    'name'    => $user['name'],
    'email'   => $user['email'],
    'role'    => $user['role'],
    'company' => $user['company'],
]);

json_response([
    'token' => $token,
    'user'  => [
        'id'      => $user['id'],
        'name'    => $user['name'],
        'email'   => $user['email'],
        'company' => $user['company'],
        'role'    => $user['role'],
    ],
]);
