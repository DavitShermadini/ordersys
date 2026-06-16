<?php
require_once __DIR__ . '/../../api/bootstrap.php';

$admin = require_admin();

$method = $_SERVER['REQUEST_METHOD'];

// GET — მომხმარებლების სია
if ($method === 'GET') {
    $id     = (int) ($_GET['id'] ?? 0);
    $search = trim($_GET['q'] ?? '');

    if ($id) {
        $stmt = $pdo->prepare("SELECT id, name, email, company, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) error_response('მომხმარებელი ვერ მოიძებნა.', 404);
        json_response($u);
    }

    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT id, name, email, company, role, created_at FROM users WHERE name LIKE ? OR email LIKE ? OR company LIKE ? ORDER BY created_at DESC");
        $like = '%'.$search.'%';
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $pdo->query("SELECT id, name, email, company, role, created_at FROM users ORDER BY created_at DESC");
    }
    json_response($stmt->fetchAll());
}

// POST — ახალი მომხმარებელი
if ($method === 'POST') {
    $b        = body();
    $name     = trim($b['name'] ?? '');
    $email    = trim($b['email'] ?? '');
    $company  = trim($b['company'] ?? '');
    $role     = in_array($b['role'] ?? '', ['admin','customer']) ? $b['role'] : 'customer';
    $password = $b['password'] ?? '';

    if (!$name || !$email || !$password) error_response('სახელი, ელ-ფოსტა და პაროლი სავალდებულოა.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('არასწორი ელ-ფოსტის ფორმატი.');
    if (strlen($password) < 6) error_response('პაროლი მინიმუმ 6 სიმბოლოს უნდა შეიცავდეს.');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) error_response('ეს ელ-ფოსტა უკვე გამოყენებულია.', 409);

    $pdo->prepare("INSERT INTO users (name, email, company, role, password) VALUES (?,?,?,?,?)")
        ->execute([$name, $email, $company ?: null, $role, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id, name, email, company, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    json_response($stmt->fetch(), 201);
}

// PUT — მომხმარებლის განახლება
if ($method === 'PUT') {
    $b        = body();
    $id       = (int) ($b['id'] ?? 0);
    $name     = trim($b['name'] ?? '');
    $email    = trim($b['email'] ?? '');
    $company  = trim($b['company'] ?? '');
    $role     = in_array($b['role'] ?? '', ['admin','customer']) ? $b['role'] : 'customer';
    $password = $b['password'] ?? '';

    if (!$id) error_response('id სავალდებულოა.');
    if (!$name || !$email) error_response('სახელი და ელ-ფოსტა სავალდებულოა.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error_response('არასწორი ელ-ფოსტის ფორმატი.');
    if ($password !== '' && strlen($password) < 6) error_response('პაროლი მინიმუმ 6 სიმბოლოს უნდა შეიცავდეს.');

    // თავიდან ვიცავთ საკუთარ role-ის შეცვლას
    if ($id === $admin['sub']) $role = $admin['role'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) error_response('ეს ელ-ფოსტა უკვე გამოყენებულია.', 409);

    if ($password !== '') {
        $pdo->prepare("UPDATE users SET name=?, email=?, company=?, role=?, password=? WHERE id=?")
            ->execute([$name, $email, $company ?: null, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
    } else {
        $pdo->prepare("UPDATE users SET name=?, email=?, company=?, role=? WHERE id=?")
            ->execute([$name, $email, $company ?: null, $role, $id]);
    }

    $stmt = $pdo->prepare("SELECT id, name, email, company, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

// DELETE — მომხმარებლის წაშლა  {"id": 1}
if ($method === 'DELETE') {
    $id = (int) (body()['id'] ?? 0);
    if (!$id) error_response('id სავალდებულოა.');
    if ($id === $admin['sub']) error_response('საკუთარი ანგარიშის წაშლა შეუძლებელია.');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) error_response('მომხმარებელი ვერ მოიძებნა.', 404);

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    json_response(['message' => 'მომხმარებელი წაიშალა.']);
}

method_not_allowed(['GET', 'POST', 'PUT', 'DELETE']);
