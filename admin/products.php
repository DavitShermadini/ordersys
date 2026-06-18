<?php
require_once '../includes/header.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pid = (int) $_POST['product_id'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
    flash('success', 'პროდუქტი წაიშალა.');
    redirect('/admin/products.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$search     = trim($_GET['q'] ?? '');
$catFilter  = (int) ($_GET['category_id'] ?? 0);

$where  = [];
$params = [];
if ($search !== '') { $where[] = 'p.name LIKE ?'; $params[] = '%'.$search.'%'; }
if ($catFilter)     { $where[] = 'p.category_id = ?'; $params[] = $catFilter; }

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-box-seam me-2"></i>პროდუქტები</h2>
    <a href="/admin/product_edit.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>პროდუქტის დამატება
    </a>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control form-control-sm" style="width:220px"
               placeholder="ძიება…" value="<?= htmlspecialchars($search) ?>">
        <?php if ($catFilter): ?>
        <input type="hidden" name="category_id" value="<?= $catFilter ?>">
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        <?php if ($search || $catFilter): ?>
        <a href="/admin/products.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </form>

    <div class="d-flex gap-1 flex-wrap ms-auto">
        <a href="/admin/products.php<?= $search ? '?q='.urlencode($search) : '' ?>"
           class="btn btn-sm <?= !$catFilter ? 'btn-dark' : 'btn-outline-secondary' ?>">ყველა</a>
        <?php foreach ($categories as $cat): ?>
        <a href="/admin/products.php?category_id=<?= $cat['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>"
           class="btn btn-sm <?= $catFilter === (int)$cat['id'] ? 'btn-dark' : 'btn-outline-secondary' ?>">
            <?= htmlspecialchars($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
        <p class="p-4 mb-0 text-muted">პროდუქტები ვერ მოიძებნა.</p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>სახელი</th>
                    <th>კატეგორია</th>
                    <th>აღწერა</th>
                    <th class="text-end">ფასი</th>
                    <th class="text-center">მარაგი</th>
                    <th>ერთ.</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
                <td>
                    <?php if ($p['category_name']): ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($p['category_name']) ?></span>
                    <?php else: ?>
                    <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small" style="max-width:200px">
                    <span class="text-truncate d-inline-block" style="max-width:190px">
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
