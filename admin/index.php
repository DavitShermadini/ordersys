<?php
require_once '../includes/header.php';
requireAdmin();

// Summary stats
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalOrders  = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Orders by status
$statusCounts = [];
$rows = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
foreach ($rows as $r) $statusCounts[$r['status']] = $r['cnt'];

// Low stock products (< 10)
$lowStock = $pdo->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC")->fetchAll();

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.*, u.name AS user_name, u.company,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-white bg-success p-3 text-center shadow-sm">
            <div class="fs-2 fw-bold">$<?= number_format($totalRevenue, 0) ?></div>
            <div class="small">Total Revenue</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-white bg-primary p-3 text-center shadow-sm">
            <div class="fs-2 fw-bold"><?= $totalOrders ?></div>
            <div class="small">Total Orders</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-white bg-info p-3 text-center shadow-sm">
            <div class="fs-2 fw-bold"><?= $totalUsers ?></div>
            <div class="small">Customers</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-white bg-secondary p-3 text-center shadow-sm">
            <div class="fs-2 fw-bold"><?= $totalProducts ?></div>
            <div class="small">Products</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $statuses = ['pending','processing','shipped','delivered','cancelled'];
    foreach ($statuses as $s):
        $count = $statusCounts[$s] ?? 0;
    ?>
    <div class="col">
        <a href="/admin/orders.php?status=<?= $s ?>" class="text-decoration-none">
            <div class="card text-center p-3 shadow-sm h-100">
                <div class="fs-4 fw-bold"><?= $count ?></div>
                <div><?= statusBadge($s) ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Recent Orders</span>
                <a href="/admin/orders.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><a href="/admin/orders.php?view=<?= $o['id'] ?>" class="fw-bold text-decoration-none">#<?= $o['id'] ?></a></td>
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
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Low Stock</span>
                <a href="/admin/products.php" class="btn btn-sm btn-outline-secondary">All Products</a>
            </div>
            <?php if (empty($lowStock)): ?>
            <div class="card-body text-muted text-center py-4">All products well-stocked.</div>
            <?php else: ?>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Product</th><th class="text-end">Stock</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lowStock as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td class="text-end">
                            <span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : 'warning' ?>">
                                <?= $p['stock'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
