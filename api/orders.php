<?php
require_once __DIR__ . '/../api/bootstrap.php';

$user = require_auth();
$uid  = $user['sub'];

$method = $_SERVER['REQUEST_METHOD'];

// GET — სია ან ერთი შეკვეთა
if ($method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $order = $stmt->fetch();
        if (!$order) error_response('შეკვეთა ვერ მოიძებნა.', 404);

        $stmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.unit
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$id]);
        $order['items'] = $stmt->fetchAll();
        json_response($order);
    }

    $stmt = $pdo->prepare("
        SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$uid]);
    json_response($stmt->fetchAll());
}

// POST — checkout (კალათიდან შეკვეთის შექმნა)
if ($method === 'POST') {
    $notes = trim(body()['notes'] ?? '');

    $stmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.quantity,
               p.id AS product_id, p.price, p.stock, p.name
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$uid]);
    $cartItems = $stmt->fetchAll();

    if (empty($cartItems)) error_response('კალათა ცარიელია.');

    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock']) {
            error_response('"' . $item['name'] . '" — მარაგში მხოლოდ ' . $item['stock'] . ' ერთეულია.');
        }
    }

    $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO orders (user_id, total, notes) VALUES (?,?,?)")
            ->execute([$uid, $total, $notes ?: null]);
        $orderId = (int) $pdo->lastInsertId();

        $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
        $upd = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($cartItems as $item) {
            $ins->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            $upd->execute([$item['quantity'], $item['product_id']]);
        }

        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$uid]);
        $pdo->commit();

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        json_response($stmt->fetch(), 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_response('გაფორმება ვერ მოხერხდა.', 500);
    }
}

method_not_allowed(['GET', 'POST']);
