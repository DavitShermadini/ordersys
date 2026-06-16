<?php
require_once '../includes/header.php';
requireAdmin();

$id      = (int) ($_GET['id'] ?? 0);
$product = null;
$errors  = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        flash('danger', 'Product not found.');
        redirect('/admin/products.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float) ($_POST['price'] ?? 0);
    $stock       = (int) ($_POST['stock'] ?? 0);
    $unit        = trim($_POST['unit'] ?? 'unit');

    if ($name === '')      $errors[] = 'Name is required.';
    if ($price <= 0)       $errors[] = 'Price must be greater than zero.';
    if ($stock < 0)        $errors[] = 'Stock cannot be negative.';
    if ($unit === '')      $unit = 'unit';

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, unit=? WHERE id=?")
                ->execute([$name, $description ?: null, $price, $stock, $unit, $id]);
            flash('success', 'Product updated.');
        } else {
            $pdo->prepare("INSERT INTO products (name, description, price, stock, unit) VALUES (?,?,?,?,?)")
                ->execute([$name, $description ?: null, $price, $stock, $unit]);
            flash('success', 'Product added.');
        }
        redirect('/admin/products.php');
    }

    // Re-populate on error
    $product = compact('name','description','price','stock','unit');
}
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="mb-4">
    <a href="/admin/products.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to Products</a>
    <h2 class="mt-1 mb-0"><?= $id ? 'Edit Product' : 'Add Product' ?></h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:640px">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0.01"
                           value="<?= htmlspecialchars($product['price'] ?? '') ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Stock</label>
                    <input type="number" name="stock" class="form-control" min="0"
                           value="<?= htmlspecialchars($product['stock'] ?? 0) ?>">
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Unit</label>
                    <input type="text" name="unit" class="form-control" placeholder="unit"
                           value="<?= htmlspecialchars($product['unit'] ?? 'unit') ?>">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $id ? 'Save Changes' : 'Add Product' ?>
                </button>
                <a href="/admin/products.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
