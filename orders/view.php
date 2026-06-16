<?php
require_once '../includes/header.php';
requireLogin();

$id  = (int) ($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $uid]);
$order = $stmt->fetch();

if (!$order) {
    flash('danger', 'Order not found.');
    redirect('/orders/index.php');
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.name AS product_name, p.unit
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/orders/index.php" class="text-muted small d-block mb-1">
            <i class="bi bi-arrow-left me-1"></i>Back to Orders
        </a>
        <h2 class="mb-0">Order #<?= $order['id'] ?></h2>
    </div>
    <?= statusBadge($order['status']) ?>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Order Items</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name'] ?? 'Deleted product') ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                        <td class="text-end fw-semibold">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Order Total</td>
                            <td class="text-end fw-bold fs-5 text-primary">$<?= number_format($order['total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Order Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Order #</dt>
                    <dd class="col-7"><?= $order['id'] ?></dd>

                    <dt class="col-5">Placed</dt>
                    <dd class="col-7"><?= date('M d, Y', strtotime($order['created_at'])) ?></dd>

                    <dt class="col-5">Updated</dt>
                    <dd class="col-7"><?= date('M d, Y', strtotime($order['updated_at'])) ?></dd>

                    <dt class="col-5">Status</dt>
                    <dd class="col-7"><?= statusBadge($order['status']) ?></dd>
                </dl>
            </div>
        </div>

        <?php if (!empty($order['notes'])): ?>
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Notes</div>
            <div class="card-body">
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
