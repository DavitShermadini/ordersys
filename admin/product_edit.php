<?php
require_once '../includes/header.php';
requireAdmin();

$id      = (int) ($_GET['id'] ?? 0);
$product = null;
$errors  = [];

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

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
    $categoryId  = (int) ($_POST['category_id'] ?? 0) ?: null;
    $removeImage = isset($_POST['remove_image']);

    if ($name === '')  $errors[] = 'სახელი სავალდებულოა.';
    if ($price <= 0)   $errors[] = 'ფასი უნდა იყოს ნულზე მეტი.';
    if ($stock < 0)    $errors[] = 'მარაგი არ შეიძლება იყოს უარყოფითი.';
    if ($unit === '')  $unit = 'unit';

    $uploadDir = __DIR__ . '/../uploads/products/';
    $newImage  = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime    = mime_content_type($file['tmp_name']);

        if (!array_key_exists($mime, $allowed)) {
            $errors[] = 'დაშვებული ფორმატებია: JPG, PNG, WebP, GIF.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'ფოტო არ უნდა აღემატებოდეს 5 MB-ს.';
        } else {
            $filename = uniqid('prod_', true) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $errors[] = 'ფოტოს ატვირთვა ვერ მოხერხდა.';
            } else {
                $newImage = $filename;
            }
        }
    }

    if (empty($errors)) {
        $currentImage = $product['image'] ?? null;

        if ($newImage) {
            if ($currentImage && file_exists($uploadDir . $currentImage)) {
                @unlink($uploadDir . $currentImage);
            }
            $finalImage = $newImage;
        } elseif ($removeImage) {
            if ($currentImage && file_exists($uploadDir . $currentImage)) {
                @unlink($uploadDir . $currentImage);
            }
            $finalImage = null;
        } else {
            $finalImage = $currentImage;
        }

        if ($id) {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, unit=?, category_id=?, image=? WHERE id=?")
                ->execute([$name, $description ?: null, $price, $stock, $unit, $categoryId, $finalImage, $id]);
            flash('success', 'პროდუქტი განახლდა.');
        } else {
            $pdo->prepare("INSERT INTO products (name, description, price, stock, unit, category_id, image) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name, $description ?: null, $price, $stock, $unit, $categoryId, $finalImage]);
            flash('success', 'პროდუქტი დაემატა.');
        }
        redirect('/admin/products.php');
    }

    $product = compact('name', 'description', 'price', 'stock', 'unit', 'categoryId');
    $product['category_id'] = $categoryId;
    $product['image']       = $newImage ?? null;
}
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="mb-4">
    <a href="/admin/products.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>პროდუქტებზე დაბრუნება</a>
    <h2 class="mt-1 mb-0"><?= $id ? 'პროდუქტის რედაქტირება' : 'პროდუქტის დამატება' ?></h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:680px">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label fw-semibold">სახელი <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">კატეგორია</label>
                <select name="category_id" class="form-select">
                    <option value="">— კატეგორიის გარეშე —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= ($product['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">აღწერა</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <!-- ─── ფოტო ─────────────────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold">პროდუქტის ფოტო</label>

                <?php if (!empty($product['image'])): ?>
                <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded bg-light">
                    <img src="/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                         alt="ფოტო" class="rounded"
                         style="width:96px;height:96px;object-fit:cover;flex-shrink:0">
                    <div>
                        <p class="mb-2 small text-muted fw-semibold">მიმდინარე ფოტო</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_image" id="removeImage">
                            <label class="form-check-label text-danger small" for="removeImage">
                                <i class="bi bi-trash3 me-1"></i>ფოტოს წაშლა
                            </label>
                        </div>
                        <p class="mt-1 mb-0 text-muted" style="font-size:.75rem">
                            ახლის ატვირთვა ავტომატურად ჩაანაცვლებს
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <input type="file" name="image" id="imageInput"
                       class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="form-text">JPG, PNG, WebP ან GIF — მაქს. 5 MB</div>

                <div id="previewWrap" class="mt-2 d-none">
                    <p class="small text-muted mb-1">გადახედვა:</p>
                    <img id="previewImg" src="#" alt="preview" class="rounded border"
                         style="width:160px;height:160px;object-fit:cover;">
                </div>
            </div>
            <!-- ────────────────────────────────────────────────────────────── -->

            <div class="row g-3 mb-4">
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

<script>
document.getElementById('imageInput').addEventListener('change', function () {
    const wrap = document.getElementById('previewWrap');
    const img  = document.getElementById('previewImg');
    if (this.files[0]) {
        img.src = URL.createObjectURL(this.files[0]);
        wrap.classList.remove('d-none');
    } else {
        wrap.classList.add('d-none');
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
