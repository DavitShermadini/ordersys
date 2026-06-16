<?php
require_once '../includes/header.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pid = (int) $_POST['product_id'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
    flash('success', 'პროდუქტი წაიშალა.');
    redirect('/admin/products.php');
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY name");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
}
$products = $stmt->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-box-seam me-2"></i>პროდუქტები</h2>
    <a href="/admin/product_edit.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>პროდუქტის დამატება
    </a>
</div>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:360px">
        <input type="text" name="q" class="form-control" placeholder="პროდუქტის ძიება…" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
        <a href="/admin/products.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
        <p class="p-4 mb-0 text-muted">პროდუქტები ვერ მოიძებნა.</p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>სახელი</th>
                    <th>აღწერა</th>
                    <th class="text-end">ფასი</th>
                    <th class="text-center">მარაგი</th>
                    <th>ერთეული</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td class="text-muted small"><?= $p['id'] ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                <td class="text-muted small" style="max-width:250px">
                    <span class="text-truncate d-inline-block" style="max-width:240px">
                        <?= htmlspecialchars($p['description'] ?? '') ?>
                    </span>
                </td>
                <td class="text-end">₾<?= number_format($p['price'], 2) ?></td>
                <td class="text-center">
                    <span class="badge bg-<?= $p['stock'] == 0 ? 'danger' : ($p['stock'] < 10 ? 'warning' : 'success') ?>">
                        <?= $p['stock'] ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($p['unit']) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/admin/product_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" onsubmit="return confirm('წაიშალოს \"<?= addslashes(htmlspecialchars($p['name'])) ?>\"?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
