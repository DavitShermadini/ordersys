<?php
require_once __DIR__ . '/../api/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') method_not_allowed(['GET']);

$search = trim($_GET['q'] ?? '');
$id     = (int) ($_GET['id'] ?? 0);

// Single product
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) error_response('პროდუქტი ვერ მოიძებნა.', 404);
    json_response($product);
}

// List
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY name");
    $stmt->execute(['%'.$search.'%', '%'.$search.'%']);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
}

json_response($stmt->fetchAll());
