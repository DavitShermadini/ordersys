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
$catId  = (int) ($_GET['category_id'] ?? 0);
$where  = [];
$params = [];
if ($search !== '') { $where[] = '(p.name LIKE ? OR p.description LIKE ?)'; $params[] = '%'.$search.'%'; $params[] = '%'.$search.'%'; }
if ($catId)         { $where[] = 'p.category_id = ?'; $params[] = $catId; }

$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY p.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_response($stmt->fetchAll());
