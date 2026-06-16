<?php
require_once '../includes/header.php';
requireAdmin();

// Toggle role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_role') {
    $uid  = (int) $_POST['user_id'];
    if ($uid === $_SESSION['user_id']) {
        flash('danger', 'You cannot change your own role.');
        redirect('/admin/users.php');
    }
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $current = $stmt->fetchColumn();
    $newRole = $current === 'admin' ? 'customer' : 'admin';
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
    flash('success', 'User role updated to ' . ucfirst($newRole) . '.');
    redirect('/admin/users.php');
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR company LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
}
$users = $stmt->fetchAll();

// Order counts per user
$orderCounts = [];
$rows = $pdo->query("SELECT user_id, COUNT(*) AS cnt FROM orders GROUP BY user_id")->fetchAll();
foreach ($rows as $r) $orderCounts[$r['user_id']] = $r['cnt'];
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-people-fill me-2"></i>Users</h2>
    <span class="badge bg-secondary fs-6"><?= count($users) ?> total</span>
</div>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:360px">
        <input type="text" name="q" class="form-control" placeholder="Search by name, email, company…" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
        <a href="/admin/users.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
        <p class="p-4 mb-0 text-muted">No users found.</p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Company</th>
                    <th class="text-center">Orders</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td class="fw-semibold">
                    <?= htmlspecialchars($u['name']) ?>
                    <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                    <span class="badge bg-secondary ms-1">You</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['company'] ?? '—') ?></td>
                <td class="text-center">
                    <?php $cnt = $orderCounts[$u['id']] ?? 0; ?>
                    <?php if ($cnt > 0): ?>
                    <a href="/admin/orders.php" class="badge bg-primary text-decoration-none"><?= $cnt ?></a>
                    <?php else: ?>
                    <span class="text-muted">0</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                        <?= ucfirst($u['role']) ?>
                    </span>
                </td>
                <td class="text-muted small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="POST" onsubmit="return confirm('Change role for <?= addslashes(htmlspecialchars($u['name'])) ?>?')">
                        <input type="hidden" name="action" value="toggle_role">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm btn-outline-<?= $u['role'] === 'admin' ? 'danger' : 'success' ?>">
                            <?= $u['role'] === 'admin' ? 'Revoke Admin' : 'Make Admin' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
