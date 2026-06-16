<?php
require_once __DIR__ . '/../../api/bootstrap.php';

require_admin();

$method   = $_SERVER['REQUEST_METHOD'];
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// GET — ყველა შეკვეთა (ფილტრით)
if ($method === 'GET') {
    $id           = (int) ($_GET['id'] ?? 0);
    $filterStatus = $_GET['status'] ?? '';

    // ერთი შეკვეთის დეტალი
    if ($id) {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name AS user_name, u.email AS user_email, u.company
            FROM orders o JOIN users u ON u.id = o.user_id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) error_response('შეკვეთა ვერ მოიძებნა.', 404);

        $stmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.unit
            FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$id]);
        $order['items'] = $stmt->fetchAll();
        json_response($order);
    }

    $params = [];
    $where  = '';
    if ($filterStatus && in_array($filterStatus, $statuses)) {
        $where    = 'WHERE o.status = ?';
        $params[] = $filterStatus;
    }

    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.company,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
        FROM orders o JOIN users u ON u.id = o.user_id
        $where
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    json_response($stmt->fetchAll());
}

// PATCH — სტატუსის განახლება  {"order_id": 1, "status": "processing"}
if ($method === 'PATCH') {
    $b       = body();
    $orderId = (int) ($b['order_id'] ?? 0);
    $status  = $b['status'] ?? '';

    if (!$orderId) error_response('order_id სავალდებულოა.');
    if (!in_array($status, $statuses)) error_response('არასწორი სტატუსი.');

    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) error_response('შეკვეთა ვერ მოიძებნა.', 404);

    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
    json_response(['message' => 'სტატუსი განახლდა.', 'status' => $status]);
}

method_not_allowed(['GET', 'PATCH']);
