<?php
require_once __DIR__ . '/../api/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = (int) ($_GET['id'] ?? 0);

// ─── GET — public ────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        if (!$banner) error_response('ბანერი ვერ მოიძებნა.', 404);
        json_response($banner);
    }

    // ?all=1 returns inactive ones too (useful for admin clients)
    $onlyActive = empty($_GET['all']);
    $sql = "SELECT * FROM banners" . ($onlyActive ? " WHERE is_active = 1" : "") . " ORDER BY sort_order, id";
    json_response($pdo->query($sql)->fetchAll());
}

// ─── POST/PUT/PATCH/DELETE — admin only ──────────────────────────────────────
require_admin();

// POST — შექმნა (multipart/form-data + ფოტო)
if ($method === 'POST') {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        error_response('image ფაილი სავალდებულოა (multipart/form-data).');
    }

    $file    = $_FILES['image'];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime    = mime_content_type($file['tmp_name']);

    if (!array_key_exists($mime, $allowed)) {
        error_response('დაშვებული ფორმატებია: JPG, PNG, WebP, GIF.');
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        error_response('ფოტო არ უნდა აღემატებოდეს 10 MB-ს.');
    }

    $filename  = uniqid('banner_', true) . '.' . $allowed[$mime];
    $uploadDir = __DIR__ . '/../uploads/banners/';
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        error_response('ფოტოს შენახვა ვერ მოხერხდა.', 500);
    }

    $title     = trim($_POST['title'] ?? '') ?: null;
    $subtitle  = trim($_POST['subtitle'] ?? '') ?: null;
    $linkUrl   = trim($_POST['link_url'] ?? '') ?: null;
    $isActive  = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
    $maxOrder  = (int) $pdo->query("SELECT COALESCE(MAX(sort_order), -1) FROM banners")->fetchColumn();

    $pdo->prepare("INSERT INTO banners (title, subtitle, image, link_url, sort_order, is_active) VALUES (?,?,?,?,?,?)")
        ->execute([$title, $subtitle, $filename, $linkUrl, $maxOrder + 1, $isActive]);
    $newId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$newId]);
    json_response($stmt->fetch(), 201);
}

// PUT — ტექსტური ველების განახლება (JSON body, ფოტო არ იცვლება)
if ($method === 'PUT') {
    if (!$id) error_response('id სავალდებულოა URL-ში (/api/banners/{id}).');

    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if (!$banner) error_response('ბანერი ვერ მოიძებნა.', 404);

    $b = body();
    $title    = array_key_exists('title', $b)    ? (trim($b['title']) ?: null)    : $banner['title'];
    $subtitle = array_key_exists('subtitle', $b) ? (trim($b['subtitle']) ?: null) : $banner['subtitle'];
    $linkUrl  = array_key_exists('link_url', $b) ? (trim($b['link_url']) ?: null) : $banner['link_url'];
    $isActive = array_key_exists('is_active', $b) ? (int) $b['is_active']         : $banner['is_active'];
    $sortOrder = array_key_exists('sort_order', $b) ? (int) $b['sort_order']      : $banner['sort_order'];

    $pdo->prepare("UPDATE banners SET title=?, subtitle=?, link_url=?, is_active=?, sort_order=? WHERE id=?")
        ->execute([$title, $subtitle, $linkUrl, $isActive, $sortOrder, $id]);

    $s = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $s->execute([$id]);
    json_response($s->fetch());
}

// PATCH — is_active toggle ან sort_order (JSON body)
if ($method === 'PATCH') {
    if (!$id) error_response('id სავალდებულოა URL-ში (/api/banners/{id}).');

    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if (!$banner) error_response('ბანერი ვერ მოიძებნა.', 404);

    $b = body();

    if (array_key_exists('is_active', $b)) {
        $pdo->prepare("UPDATE banners SET is_active = ? WHERE id = ?")
            ->execute([(int) $b['is_active'], $id]);
    }

    if (array_key_exists('sort_order', $b)) {
        $pdo->prepare("UPDATE banners SET sort_order = ? WHERE id = ?")
            ->execute([(int) $b['sort_order'], $id]);
    }

    $s = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $s->execute([$id]);
    json_response($s->fetch());
}

// DELETE — წაშლა + ფაილის წაშლა
if ($method === 'DELETE') {
    if (!$id) error_response('id სავალდებულოა URL-ში (/api/banners/{id}).');

    $stmt = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if (!$banner) error_response('ბანერი ვერ მოიძებნა.', 404);

    $file = __DIR__ . '/../uploads/banners/' . $banner['image'];
    if (file_exists($file)) @unlink($file);
    $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);

    json_response(['message' => 'ბანერი წაიშალა.']);
}

method_not_allowed(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
