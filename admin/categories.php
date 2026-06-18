<?php
require_once '../includes/header.php';
requireAdmin();

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id   = (int) $_POST['category_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        flash('danger', 'კატეგორიის წაშლა შეუძლებელია — მასში პროდუქტები არის.');
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        flash('success', 'კატეგორია წაიშალა.');
    }
    redirect('/admin/categories.php');
}

// Save (add / edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $id   = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        flash('danger', 'სახელი სავალდებულოა.');
        redirect('/admin/categories.php' . ($id ? '?edit=' . $id : ''));
    }
    if ($id) {
        $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$name, $id]);
        flash('success', 'კატეგორია განახლდა.');
    } else {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
        flash('success', 'კატეგორია დაემატა.');
    }
    redirect('/admin/categories.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCat = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCat = $stmt->fetch();
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-tags-fill me-2"></i>კატეგორიები</h2>
    <a href="/admin/categories.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>ახალი კატეგორია
    </a>
</div>

<div class="row g-4">
    <!-- სია -->
    <div class="<?= ($editCat || !$editId) && !$editId ? 'col-lg-7' : 'col-lg-6' ?>">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($categories)): ?>
                <p class="p-4 mb-0 text-muted">კატეგორიები ჯერ არ არის.</p>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>სახელი</th>
                            <th class="text-center">პროდუქტები</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr class="<?= $editId === (int)$cat['id'] ? 'table-active' : '' ?>">
                        <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                        <td class="text-center">
                            <?php if ($cat['product_count'] > 0): ?>
                            <a href="/admin/products.php?category_id=<?= $cat['id'] ?>"
                               class="badge bg-primary text-decoration-none"><?= $cat['product_count'] ?></a>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/admin/categories.php?edit=<?= $cat['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST"
                                      onsubmit="return confirm('წაიშალოს \"<?= addslashes(htmlspecialchars($cat['name'])) ?>\"?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"
                                            <?= $cat['product_count'] > 0 ? 'disabled title="პროდუქტები ჯერ გადაიტანე"' : '' ?>>
                                        <i class="bi bi-trash3"></i>
                                    </button>
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
    </div>

    <!-- ფორმა -->
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                <?= $editCat ? 'კატეგორიის რედაქტირება' : 'ახალი კატეგორია' ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="category_id" value="<?= $editCat['id'] ?? 0 ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">სახელი <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" autofocus required
                               value="<?= htmlspecialchars($editCat['name'] ?? '') ?>"
                               placeholder="მაგ: ტკბილეული">
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $editCat ? 'შენახვა' : 'დამატება' ?>
                        </button>
                        <?php if ($editCat): ?>
                        <a href="/admin/categories.php" class="btn btn-outline-secondary">გაუქმება</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
