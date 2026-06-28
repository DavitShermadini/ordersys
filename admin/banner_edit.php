<?php
require_once '../includes/header.php';
requireAdmin();

$id     = (int) ($_GET['id'] ?? 0);
$banner = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if (!$banner) {
        flash('danger', 'ბანერი ვერ მოიძებნა.');
        redirect('/admin/banners.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $subtitle  = trim($_POST['subtitle'] ?? '');
    $linkUrl   = trim($_POST['link_url'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    $uploadDir = __DIR__ . '/../uploads/banners/';
    $newImage  = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime    = mime_content_type($file['tmp_name']);

        if (!array_key_exists($mime, $allowed)) {
            $errors[] = 'დაშვებული ფორმატებია: JPG, PNG, WebP, GIF.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'ფოტო არ უნდა აღემატებოდეს 10 MB-ს.';
        } else {
            $filename = uniqid('banner_', true) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $errors[] = 'ფოტოს ატვირთვა ვერ მოხერხდა.';
            } else {
                $newImage = $filename;
            }
        }
    } elseif (!$id) {
        $errors[] = 'ფოტო სავალდებულოა ახალი ბანერისთვის.';
    }

    if (empty($errors)) {
        if ($id) {
            $finalImage = $banner['image'];
            if ($newImage) {
                $old = $uploadDir . $banner['image'];
                if (file_exists($old)) @unlink($old);
                $finalImage = $newImage;
            }
            $pdo->prepare("UPDATE banners SET title=?, subtitle=?, image=?, link_url=?, sort_order=?, is_active=? WHERE id=?")
                ->execute([$title ?: null, $subtitle ?: null, $finalImage, $linkUrl ?: null, $sortOrder, $isActive, $id]);
            flash('success', 'ბანერი განახლდა.');
        } else {
            $maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(sort_order), -1) FROM banners")->fetchColumn();
            $pdo->prepare("INSERT INTO banners (title, subtitle, image, link_url, sort_order, is_active) VALUES (?,?,?,?,?,?)")
                ->execute([$title ?: null, $subtitle ?: null, $newImage, $linkUrl ?: null, $maxOrder + 1, $isActive]);
            flash('success', 'ბანერი დაემატა.');
        }
        redirect('/admin/banners.php');
    }
}
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="mb-4">
    <a href="/admin/banners.php" class="text-muted small">
        <i class="bi bi-arrow-left me-1"></i>ბანერებზე დაბრუნება
    </a>
    <h2 class="mt-1 mb-0"><?= $id ? 'ბანერის რედაქტირება' : 'ახალი ბანერი' ?></h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:700px">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">

            <!-- ─── ფოტო ──────────────────────────────────────────────────── -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    ბანერის ფოტო <?php if (!$id): ?><span class="text-danger">*</span><?php endif; ?>
                </label>

                <?php if ($banner && $banner['image']): ?>
                <div class="mb-3 p-2 border rounded bg-light">
                    <p class="small text-muted fw-semibold mb-2">მიმდინარე ფოტო:</p>
                    <img src="/uploads/banners/<?= htmlspecialchars($banner['image']) ?>"
                         alt="banner" class="rounded img-fluid"
                         style="max-height:200px;object-fit:cover;display:block">
                    <p class="mt-2 mb-0 text-muted" style="font-size:.75rem">
                        ახლის ატვირთვა ავტომატურად ჩაანაცვლებს
                    </p>
                </div>
                <?php endif; ?>

                <input type="file" name="image" id="imageInput"
                       class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="form-text">JPG, PNG, WebP ან GIF — მაქს. 10 MB. რეკომენდებული ზომა: 1400×400px</div>

                <div id="previewWrap" class="mt-3 d-none">
                    <p class="small text-muted mb-1">გადახედვა:</p>
                    <img id="previewImg" src="#" alt="preview" class="rounded border img-fluid"
                         style="max-height:200px;object-fit:cover;display:block">
                </div>
            </div>

            <!-- ─── სათაური ───────────────────────────────────────────────── -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    სათაური <span class="text-muted fw-normal">(სურვილისამებრ)</span>
                </label>
                <input type="text" name="title" class="form-control"
                       value="<?= htmlspecialchars($banner['title'] ?? '') ?>"
                       placeholder="მაგ: ზაფხულის სეზონი დაიწყო">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    ქვესათაური <span class="text-muted fw-normal">(სურვილისამებრ)</span>
                </label>
                <input type="text" name="subtitle" class="form-control"
                       value="<?= htmlspecialchars($banner['subtitle'] ?? '') ?>"
                       placeholder="მაგ: შეარჩიეთ საუკეთესო პროდუქტები">
            </div>

            <!-- ─── ბმული ─────────────────────────────────────────────────── -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    ბმული <span class="text-muted fw-normal">(სურვილისამებრ)</span>
                </label>
                <input type="text" name="link_url" class="form-control"
                       value="<?= htmlspecialchars($banner['link_url'] ?? '') ?>"
                       placeholder="მაგ: /products/index.php?category_id=3">
                <div class="form-text">თუ მიუთითებთ, ბანერზე დაჭერა ამ მისამართზე გადაიყვანს.</div>
            </div>

            <!-- ─── პარამეტრები ───────────────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">რიგი (sort order)</label>
                    <input type="number" name="sort_order" class="form-control" min="0"
                           value="<?= htmlspecialchars($banner['sort_order'] ?? 0) ?>">
                    <div class="form-text">მცირე რიცხვი — პირველი</div>
                </div>
                <div class="col-sm-8 d-flex align-items-end pb-1">
                    <div class="form-check form-switch fs-5">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                               <?= ($banner['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isActive">ჩართული</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $id ? 'შენახვა' : 'ბანერის დამატება' ?>
                </button>
                <a href="/admin/banners.php" class="btn btn-outline-secondary">გაუქმება</a>
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
