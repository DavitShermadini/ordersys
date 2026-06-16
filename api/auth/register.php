<?php
require_once __DIR__ . '/../../api/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') method_not_allowed(['POST']);

$b        = body();
$name     = trim($b['name'] ?? '');
$email    = trim($b['email'] ?? '');
$company  = trim($b['company'] ?? '');
$password = $b['password'] ?? '';

if (!$name || !$email || !$password) error_response('სახელი, ელ-ფოსტა და პაროლი სავალდებულოა.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('არასწორი ელ-ფოსტის ფორმატი.');
if (strlen($password) < 6) error_response('პაროლი მინიმუმ 6 სიმბოლოს უნდა შეიცავდეს.');

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) error_response('ეს ელ-ფოსტა უკვე რეგისტრირებულია.', 409);

$pdo->prepare("INSERT INTO users (name, email, company, password) VALUES (?,?,?,?)")
    ->execute([$name, $email, $company ?: null, password_hash($password, PASSWORD_DEFAULT)]);

$id   = (int) $pdo->lastInsertId();
$token = jwt_encode(['sub' => $id, 'name' => $name, 'email' => $email, 'role' => 'customer', 'company' => $company ?: null]);

json_response([
    'token' => $token,
    'user'  => [
        'id'      => $id,
        'name'    => $name,
        'email'   => $email,
        'company' => $company ?: null,
        'role'    => 'customer',
    ],
], 201);
