<?php
require_once __DIR__ . '/../api/bootstrap.php';

$user = require_auth();
$uid  = $user['sub'];

$method = $_SERVER['REQUEST_METHOD'];

// GET — კალათის ჩვენება
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.quantity,
               p.id AS product_id, p.name, p.price, p.stock, p.unit,
               (p.price * c.quantity) AS subtotal
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll();
    $total = array_sum(array_column($items, 'subtotal'));
    json_response(['items' => $items, 'total' => round($total, 2)]);
}

// POST — კალათაში დამატება  {"product_id": 1, "quantity": 2}
if ($method === 'POST') {
    $b      = body();
    $pid    = (int) ($b['product_id'] ?? 0);
    $qty    = max(1, (int) ($b['quantity'] ?? 1));

    if (!$pid) error_response('product_id სავალდებულოა.');

    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $product = $stmt->fetch();

    if (!$product) error_response('პროდუქტი ვერ მოიძებნა.', 404);
    if ($product['stock'] < 1) error_response('პროდუქტი არ არის მარაგში.');

    $pdo->prepare("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ")->execute([$uid, $pid, $qty]);

    json_response(['message' => 'პროდუქტი კალათაში დაემატა.'], 201);
}

// PUT — რაოდენობის განახლება  {"cart_id": 1, "quantity": 3}
if ($method === 'PUT') {
    $b      = body();
    $cartId = (int) ($b['cart_id'] ?? 0);
    $qty    = (int) ($b['quantity'] ?? 0);

    if (!$cartId) error_response('cart_id სავალდებულოა.');

    if ($qty <= 0) {
        $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$cartId, $uid]);
        json_response(['message' => 'პოზიცია წაიშალა.']);
    }

    $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")->execute([$qty, $cartId, $uid]);
    json_response(['message' => 'კალათა განახლდა.']);
}

// DELETE — პოზიციის წაშლა  {"cart_id": 1}
if ($method === 'DELETE') {
    $b      = body();
    $cartId = (int) ($b['cart_id'] ?? 0);
    if (!$cartId) error_response('cart_id სავალდებულოა.');
    $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$cartId, $uid]);
    json_response(['message' => 'პოზიცია კალათიდან წაიშალა.']);
}

method_not_allowed(['GET', 'POST', 'PUT', 'DELETE']);
