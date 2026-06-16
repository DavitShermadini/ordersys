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
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, company, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $company ?: null, $hash]);
            flash('success', 'Account created successfully! Please log in.');
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
        <h3 class="mt-2">Create Account</h3>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Company Name</label>
            <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>" placeholder="Optional">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="6">
            <div class="form-text">Minimum 6 characters.</div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="confirm" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
    </form>
    <hr>
    <p class="text-center mb-0 text-muted">Already have an account? <a href="/auth/login.php">Login</a></p>
</div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
