<?php require_once 'includes/header.php'; ?>

<?php if (!isLoggedIn()): ?>
<div class="py-5 text-center">
    <i class="bi bi-box-seam-fill display-1 text-primary"></i>
    <h1 class="display-5 fw-bold mt-3">B2B Order Management</h1>
    <p class="col-lg-6 mx-auto lead text-muted">
        Browse our product catalog, build your order, and track every shipment in one place.
    </p>
    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
        <a href="/auth/register.php" class="btn btn-primary btn-lg px-5">Get Started</a>
        <a href="/auth/login.php" class="btn btn-outline-secondary btn-lg px-5">Login</a>
    </div>
</div>

<?php else: ?>

<?php
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$uid]);
$totalOrders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$uid]);
$pendingOrders = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0");
$availableProducts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
$stmt->execute([$uid]);
$cartItems = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$uid]);
$recentOrders = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
        <?php if (!empty($_SESSION['user_company'])): ?>
        <span class="text-muted"><?= htmlspecialchars($_SESSION['user_company']) ?></span>
        <?php endif; ?>
    </div>
    <a href="/products/index.php" class="btn btn-primary"><i class="bi bi-grid-fill me-1"></i>Browse Products</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-white bg-primary p-3 text-center">
            <div class="fs-2 fw-bold"><?= $totalOrders ?></div>
            <div class="small">Total Orders</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-white bg-warning p-3 text-center">
            <div class="fs-2 fw-bold"><?= $pendingOrders ?></div>
            <div class="small">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-white bg-success p-3 text-center">
            <div class="fs-2 fw-bold"><?= $availableProducts ?></div>
            <div class="small">Products Available</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-white bg-info p-3 text-center">
            <div class="fs-2 fw-bold"><?= $cartItems ?></div>
            <div class="small">Items in Cart</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent Orders</span>
        <a href="/orders/index.php" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentOrders)): ?>
        <p class="p-4 mb-0 text-muted">No orders yet. <a href="/products/index.php">Start browsing products</a>.</p>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr>
                <td class="align-middle fw-bold">#<?= $o['id'] ?></td>
                <td class="align-middle"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                <td class="align-middle"><?= $o['item_count'] ?></td>
                <td class="align-middle">$<?= number_format($o['total'], 2) ?></td>
                <td class="align-middle"><?= statusBadge($o['status']) ?></td>
                <td class="align-middle">
                    <a href="/orders/view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
