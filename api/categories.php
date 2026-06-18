<?php
require_once __DIR__ . '/../api/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int) ($_GET['id'] ?? 0);

// GET — public: სია ან ერთი კატეგორია
if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if (!$cat) error_response('კატეგორია ვერ მოიძებნა.', 404);

        $stmt = $pdo->prepare("
            SELECT id, name, price, stock, unit
            FROM products WHERE category_id = ? ORDER BY name
        ");
        $stmt->execute([$id]);
        $cat['products'] = $stmt->fetchAll();
        json_response($cat);
    }

    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id
        ORDER BY c.name
    ");
    json_response($stmt->fetchAll());
}

// POST / PUT / DELETE — admin only
require_admin();

// POST — შექმნა  body: {"name": "კატეგორიის სახელი"}
if ($method === 'POST') {
    $name = trim(body()['name'] ?? '');
    if (!$name) error_response('სახელი სავალდებულოა.');

    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) error_response('ასეთი კატეგორია უკვე არსებობს.', 409);

    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
    $newId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$newId]);
    json_response($stmt->fetch(), 201);
}

// PUT — განახლება  body: {"name": "ახალი სახელი"}
if ($method === 'PUT') {
    if (!$id) error_response('id სავალდებულოა URL-ში (/api/categories/{id}).');
    $name = trim(body()['name'] ?? '');
    if (!$name) error_response('სახელი სავალდებულოა.');

    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) error_response('კატეგორია ვერ მოიძებნა.', 404);

    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) error_response('ეს სახელი უკვე გამოყენებულია.', 409);

    $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $id]);

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

// DELETE — წაშლა (მხოლოდ თუ პროდუქტები არ ჰყავს)
if ($method === 'DELETE') {
    if (!$id) error_response('id სავალდებულოა URL-ში (/api/categories/{id}).');

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    if ((int) $stmt->fetch()['cnt'] > 0) {
        error_response('კატეგორიაში პროდუქტებია — ჯერ გადაიყვანეთ სხვა კატეგორიაში.', 409);
    }

    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    json_response(['message' => 'კატეგორია წაიშალა.']);
}

method_not_allowed(['GET', 'POST', 'PUT', 'DELETE']);
