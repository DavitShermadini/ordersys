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
        flash('danger', 'პროდუქტი ვერ მოიძებნა.');
        redirect('/admin/products.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float) ($_POST['price'] ?? 0);
    $stock       = (int) ($_POST['stock'] ?? 0);
    $unit        = trim($_POST['unit'] ?? 'unit');

    if ($name === '')  $errors[] = 'სახელი სავალდებულოა.';
    if ($price <= 0)   $errors[] = 'ფასი უნდა იყოს ნულზე მეტი.';
    if ($stock < 0)    $errors[] = 'მარაგი არ შეიძლება იყოს უარყოფითი.';
    if ($unit === '')  $unit = 'unit';

    if (empty($errors)) {
        if ($id) {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, unit=? WHERE id=?")
                ->execute([$name, $description ?: null, $price, $stock, $unit, $id]);
            flash('success', 'პროდუქტი განახლდა.');
        } else {
            $pdo->prepare("INSERT INTO products (name, description, price, stock, unit) VALUES (?,?,?,?,?)")
                ->execute([$name, $description ?: null, $price, $stock, $unit]);
            flash('success', 'პროდუქტი დაემატა.');
        }
        redirect('/admin/products.php');
    }

    $product = compact('name','description','price','stock','unit');
}
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="mb-4">
    <a href="/admin/products.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>პროდუქტებზე დაბრუნება</a>
    <h2 class="mt-1 mb-0"><?= $id ? 'პროდუქტის რედაქტირება' : 'პროდუქტის დამატება' ?></h2>
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
                <label class="form-label fw-semibold">სახელი <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">აღწერა</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">ფასი (₾) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0.01"
                           value="<?= htmlspecialchars($product['price'] ?? '') ?>" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">მარაგი</label>
                    <input type="number" name="stock" class="form-control" min="0"
                           value="<?= htmlspecialchars($product['stock'] ?? 0) ?>">
                </div>
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">ერთეული</label>
                    <input type="text" name="unit" class="form-control" placeholder="unit"
                           value="<?= htmlspecialchars($product['unit'] ?? 'unit') ?>">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $id ? 'შენახვა' : 'პროდუქტის დამატება' ?>
                </button>
                <a href="/admin/products.php" class="btn btn-outline-secondary">გაუქმება</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
