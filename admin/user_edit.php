<?php
require_once '../includes/header.php';
requireAdmin();

$id      = (int) ($_GET['id'] ?? 0);
$user    = null;
$errors  = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash('danger', 'მომხმარებელი ვერ მოიძებნა.');
        redirect('/admin/users.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $company  = trim($_POST['company'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','customer']) ? $_POST['role'] : 'customer';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '')
        $errors[] = 'სახელი სავალდებულოა.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'სწორი ელ-ფოსტა სავალდებულოა.';
    if (!$id && $password === '')
        $errors[] = 'პაროლი სავალდებულოა.';
    if ($password !== '' && strlen($password) < 6)
        $errors[] = 'პაროლი მინიმუმ 6 სიმბოლოს უნდა შეიცავდეს.';
    if ($password !== '' && $password !== $confirm)
        $errors[] = 'პაროლები არ ემთხვევა.';

    if (empty($errors)) {
        // Check email uniqueness (exclude current user on edit)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'ამ ელ-ფოსტით მომხმარებელი უკვე არსებობს.';
        }
    }

    if (empty($errors)) {
        if ($id) {
            if ($password !== '') {
                $pdo->prepare("UPDATE users SET name=?, email=?, company=?, role=?, password=? WHERE id=?")
                    ->execute([$name, $email, $company ?: null, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $pdo->prepare("UPDATE users SET name=?, email=?, company=?, role=? WHERE id=?")
                    ->execute([$name, $email, $company ?: null, $role, $id]);
            }
            flash('success', 'მომხმარებელი განახლდა.');
        } else {
            $pdo->prepare("INSERT INTO users (name, email, company, role, password) VALUES (?,?,?,?,?)")
                ->execute([$name, $email, $company ?: null, $role, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'მომხმარებელი დაემატა.');
        }
        redirect('/admin/users.php');
    }

    $user = compact('name','email','company','role');
}
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="mb-4">
    <a href="/admin/users.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>მომხმარებლებზე დაბრუნება</a>
    <h2 class="mt-1 mb-0"><?= $id ? 'მომხმარებლის რედაქტირება' : 'მომხმარებლის დამატება' ?></h2>
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

<div class="card shadow-sm" style="max-width:600px">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">სახელი და გვარი <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">ელ-ფოსტა <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">კომპანია</label>
                <input type="text" name="company" class="form-control" placeholder="სურვილისამებრ"
                       value="<?= htmlspecialchars($user['company'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">როლი</label>
                <select name="role" class="form-select"
                        <?= $id && $id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                    <option value="customer" <?= ($user['role'] ?? 'customer') === 'customer' ? 'selected' : '' ?>>მომხმარებელი</option>
                    <option value="admin"    <?= ($user['role'] ?? '') === 'admin'    ? 'selected' : '' ?>>ადმინი</option>
                </select>
                <?php if ($id && $id === (int)$_SESSION['user_id']): ?>
                <input type="hidden" name="role" value="<?= htmlspecialchars($user['role']) ?>">
                <div class="form-text text-warning">საკუთარი როლის შეცვლა შეუძლებელია.</div>
                <?php endif; ?>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    პაროლი <?= $id ? '<span class="text-muted fw-normal">(შევსების შემთხვევაში შეიცვლება)</span>' : '<span class="text-danger">*</span>' ?>
                </label>
                <input type="password" name="password" class="form-control" minlength="6"
                       <?= !$id ? 'required' : '' ?> placeholder="მინიმუმ 6 სიმბოლო">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">პაროლის დადასტურება <?= !$id ? '<span class="text-danger">*</span>' : '' ?></label>
                <input type="password" name="confirm" class="form-control" <?= !$id ? 'required' : '' ?>>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $id ? 'შენახვა' : 'მომხმარებლის დამატება' ?>
                </button>
                <a href="/admin/users.php" class="btn btn-outline-secondary">გაუქმება</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
