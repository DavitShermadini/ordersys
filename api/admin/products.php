<?php
require_once __DIR__ . '/../../api/bootstrap.php';

require_admin();

$method = $_SERVER['REQUEST_METHOD'];

// GET — სია ან ერთი პროდუქტი
if ($method === 'GET') {
    $id     = (int) ($_GET['id'] ?? 0);
    $search = trim($_GET['q'] ?? '');

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) error_response('პროდუქტი ვერ მოიძებნა.', 404);
        json_response($p);
    }

    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY name");
        $stmt->execute(['%'.$search.'%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    }
    json_response($stmt->fetchAll());
}

// POST — ახალი პროდუქტი
if ($method === 'POST') {
    $b    = body();
    $name = trim($b['name'] ?? '');
    $desc = trim($b['description'] ?? '');
    $price = (float) ($b['price'] ?? 0);
    $stock = (int)  ($b['stock'] ?? 0);
    $unit  = trim($b['unit'] ?? 'unit') ?: 'unit';

    if ($name === '')  error_response('სახელი სავალდებულოა.');
    if ($price <= 0)   error_response('ფასი უნდა იყოს ნულზე მეტი.');
    if ($stock < 0)    error_response('მარაგი არ შეიძლება იყოს უარყოფითი.');

    $pdo->prepare("INSERT INTO products (name, description, price, stock, unit) VALUES (?,?,?,?,?)")
        ->execute([$name, $desc ?: null, $price, $stock, $unit]);
    $id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    json_response($stmt->fetch(), 201);
}

// PUT — პროდუქტის განახლება
if ($method === 'PUT') {
    $b     = body();
    $id    = (int) ($b['id'] ?? 0);
    $name  = trim($b['name'] ?? '');
    $desc  = trim($b['description'] ?? '');
    $price = (float) ($b['price'] ?? 0);
    $stock = (int)   ($b['stock'] ?? 0);
    $unit  = trim($b['unit'] ?? 'unit') ?: 'unit';

    if (!$id)          error_response('id სავალდებულოა.');
    if ($name === '')  error_response('სახელი სავალდებულოა.');
    if ($price <= 0)   error_response('ფასი უნდა იყოს ნულზე მეტი.');
    if ($stock < 0)    error_response('მარაგი არ შეიძლება იყოს უარყოფითი.');

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) error_response('პროდუქტი ვერ მოიძებნა.', 404);

    $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, unit=? WHERE id=?")
        ->execute([$name, $desc ?: null, $price, $stock, $unit, $id]);

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

// DELETE — პროდუქტის წაშლა  {"id": 1}
if ($method === 'DELETE') {
    $id = (int) (body()['id'] ?? 0);
    if (!$id) error_response('id სავალდებულოა.');

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) error_response('პროდუქტი ვერ მოიძებნა.', 404);

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    json_response(['message' => 'პროდუქტი წაიშალა.']);
}

method_not_allowed(['GET', 'POST', 'PUT', 'DELETE']);
