<?php
require_once '../includes/header.php';
requireLogin();

$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$uid]);
$orders = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-list-ul me-2"></i>My Orders</h2>
    <a href="/products/index.php" class="btn btn-primary"><i class="bi bi-grid-fill me-1"></i>Browse Products</a>
</div>

<?php if (empty($orders)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox display-3 d-block mb-3"></i>
        <p class="mb-0">No orders yet. <a href="/products/index.php">Start browsing products</a>.</p>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="fw-bold">#<?= $o['id'] ?></td>
                <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                <td><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                <td class="fw-semibold">$<?= number_format($o['total'], 2) ?></td>
                <td><?= statusBadge($o['status']) ?></td>
                <td>
                    <a href="/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
