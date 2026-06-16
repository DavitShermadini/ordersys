<?php
require_once '../includes/header.php';
if (isLoggedIn()) redirect('/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $company  = trim($_POST['company'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password) {
        $error = 'გთხოვთ შეავსოთ ყველა სავალდებულო ველი.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'შეიყვანეთ სწორი ელ-ფოსტის მისამართი.';
    } elseif (strlen($password) < 6) {
        $error = 'პაროლი მინიმუმ 6 სიმბოლოს უნდა შეიცავდეს.';
    } elseif ($password !== $confirm) {
        $error = 'პაროლები არ ემთხვევა.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'ამ ელ-ფოსტით ანგარიში უკვე არსებობს.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, company, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $company ?: null, $hash]);
            flash('success', 'ანგარიში წარმატებით შეიქმნა! გთხოვთ შეხვიდეთ.');
            redirect('/auth/login.php');
        }
    }
}
?>

<div class="row justify-content-center">
<div class="col-md-6 col-lg-5">
<div class="card shadow-sm">
<div class="card-body p-4">
    <div class="text-center mb-4">
        <i class="bi bi-person-plus-fill display-5 text-primary"></i>
        <h3 class="mt-2">ანგარიშის შექმნა</h3>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">სახელი და გვარი <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">ელ-ფოსტა <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">კომპანიის სახელი</label>
            <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>" placeholder="სურვილისამებრ">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">პაროლი <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="6">
            <div class="form-text">მინიმუმ 6 სიმბოლო.</div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">პაროლის დადასტურება <span class="text-danger">*</span></label>
            <input type="password" name="confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">ანგარიშის შექმნა</button>
    </form>
    <hr>
    <p class="text-center mb-0 text-muted">უკვე გაქვთ ანგარიში? <a href="/auth/login.php">შესვლა</a></p>
</div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
