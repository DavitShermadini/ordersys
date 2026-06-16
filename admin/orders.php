<?php
require_once '../includes/header.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    $status  = in_array($_POST['status'], $allowed) ? $_POST['status'] : null;
    if ($status) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$status, (int) $_POST['order_id']]);
        flash('success', 'Order #' . (int)$_POST['order_id'] . ' status updated to ' . ucfirst($status) . '.');
    }
    $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'view' => $_GET['view'] ?? '']));
    redirect('/admin/orders.php' . ($qs ? '?' . $qs : ''));
}

$filterStatus = $_GET['status'] ?? '';
$viewId       = (int) ($_GET['view'] ?? 0);

// Single order view
$viewOrder = null;
$viewItems = [];
if ($viewId) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.company
        FROM orders o JOIN users u ON u.id = o.user_id
        WHERE o.id = ?
    ");
    $stmt->execute([$viewId]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.unit
            FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$viewId]);
        $viewItems = $stmt->fetchAll();
    }
}

// Orders list
$params = [];
$where  = '';
if ($filterStatus) {
    $where    = 'WHERE o.status = ?';
    $params[] = $filterStatus;
}
$orders = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.company,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o JOIN users u ON u.id = o.user_id
    $where
    ORDER BY o.created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

$statuses = ['pending','processing','shipped','delivered','cancelled'];
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-list-ul me-2"></i>Orders</h2>
</div>

<!-- Status filter tabs -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="/admin/orders.php" class="btn btn-sm <?= !$filterStatus ? 'btn-dark' : 'btn-outline-secondary' ?>">All</a>
    <?php foreach ($statuses as $s): ?>
    <a href="/admin/orders.php?status=<?= $s ?>"
       class="btn btn-sm <?= $filterStatus === $s ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Orders table -->
    <div class="<?= $viewOrder ? 'col-lg-6' : 'col-12' ?>">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                <p class="p-4 mb-0 text-muted">No orders found.</p>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr class="<?= $viewId == $o['id'] ? 'table-active' : '' ?>">
                        <td class="fw-bold">#<?= $o['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($o['user_name']) ?></div>
                            <?php if ($o['company']): ?>
                            <div class="text-muted small"><?= htmlspecialchars($o['company']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= $o['item_count'] ?></td>
                        <td>$<?= number_format($o['total'], 2) ?></td>
                        <td><?= statusBadge($o['status']) ?></td>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <a href="/admin/orders.php?view=<?= $o['id'] ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order detail panel -->
    <?php if ($viewOrder): ?>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Order #<?= $viewOrder['id'] ?></span>
                <a href="/admin/orders.php<?= $filterStatus ? '?status=' . urlencode($filterStatus) : '' ?>"
                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
            <div class="card-body">
                <!-- Customer info -->
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="fw-semibold"><?= htmlspecialchars($viewOrder['user_name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($viewOrder['user_email']) ?></div>
                    <?php if ($viewOrder['company']): ?>
                    <div class="text-muted small"><?= htmlspecialchars($viewOrder['company']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <table class="table table-sm mb-3">
                    <thead class="table-light">
                        <tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($viewItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name'] ?? 'Deleted') ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                        <td class="text-end">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-primary">$<?= number_format($viewOrder['total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($viewOrder['notes']): ?>
                <div class="mb-3 p-2 bg-light rounded text-muted small">
                    <strong>Notes:</strong> <?= nl2br(htmlspecialchars($viewOrder['notes'])) ?>
                </div>
                <?php endif; ?>

                <!-- Status update -->
                <form method="POST" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary text-nowrap">Update Status</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
