<?php
require_once '../includes/header.php';
if (isLoggedIn()) redirect('/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['user_role']    = $user['role'];
            $_SESSION['user_company'] = $user['company'];
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('/index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<div class="row justify-content-center">
<div class="col-md-5 col-lg-4">
<div class="card shadow-sm">
<div class="card-body p-4">
    <div class="text-center mb-4">
        <i class="bi bi-box-seam-fill display-5 text-primary"></i>
        <h3 class="mt-2">Sign In</h3>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
    </form>
    <hr>
    <p class="text-center mb-0 text-muted">Don't have an account? <a href="/auth/register.php">Register</a></p>
</div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
