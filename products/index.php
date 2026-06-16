<?php
require_once '../includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $pid = (int) $_POST['product_id'];
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $product = $stmt->fetch();

    if ($product && $product['stock'] > 0) {
        $pdo->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ")->execute([$_SESSION['user_id'], $pid, $qty]);
        flash('success', 'პროდუქტი კალათაში დაემატა.');
    } else {
        flash('danger', 'პროდუქტი არ არის მარაგში.');
    }
    redirect('/products/index.php' . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY name");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
}
$products = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-grid-fill me-2"></i>პროდუქტები</h2>
    <a href="/cart/index.php" class="btn btn-outline-primary">
        <i class="bi bi-cart-fill me-1"></i>კალათის ნახვა
        <?php if (($cartCount ?? 0) > 0): ?>
        <span class="badge bg-danger ms-1"><?= $cartCount ?? 0 ?></span>
        <?php endif; ?>
    </a>
</div>

<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="პროდუქტების ძიება…" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
        <a href="/products/index.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($products)): ?>
<div class="alert alert-info">პროდუქტები ვერ მოიძებნა<?= $search ? ' — "' . htmlspecialchars($search) . '"' : '' ?>.</div>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
<?php foreach ($products as $p): ?>
<div class="col">
    <div class="card h-100 shadow-sm">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($p['description'] ?? '') ?></p>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fs-5 fw-bold text-primary">₾<?= number_format($p['price'], 2) ?></span>
                <span class="text-muted small"><?= htmlspecialchars($p['unit']) ?>-ზე</span>
            </div>
            <?php if ($p['stock'] > 0): ?>
            <p class="text-success small mb-2"><i class="bi bi-check-circle-fill me-1"></i>მარაგში: <?= $p['stock'] ?></p>
            <form method="POST" class="d-flex gap-2">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>"
                       class="form-control form-control-sm" style="width:75px">
                <button class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-cart-plus me-1"></i>კალათაში დამატება
                </button>
            </form>
            <?php else: ?>
            <span class="badge bg-secondary">არ არის მარაგში</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
