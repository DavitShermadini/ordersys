<?php
require_once '../includes/header.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_role') {
    $uid = (int) $_POST['user_id'];
    if ($uid === $_SESSION['user_id']) {
        flash('danger', 'საკუთარი როლის შეცვლა შეუძლებელია.');
        redirect('/admin/users.php');
    }
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $current = $stmt->fetchColumn();
    $newRole = $current === 'admin' ? 'customer' : 'admin';
    $roleLabel = $newRole === 'admin' ? 'ადმინი' : 'მომხმარებელი';
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
    flash('success', 'მომხმარებლის როლი განახლდა: ' . $roleLabel . '.');
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

$orderCounts = [];
$rows = $pdo->query("SELECT user_id, COUNT(*) AS cnt FROM orders GROUP BY user_id")->fetchAll();
foreach ($rows as $r) $orderCounts[$r['user_id']] = $r['cnt'];
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-people-fill me-2"></i>მომხმარებლები</h2>
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-secondary fs-6">სულ: <?= count($users) ?></span>
        <a href="/admin/user_edit.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>მომხმარებლის დამატება
        </a>
    </div>
</div>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:360px">
        <input type="text" name="q" class="form-control" placeholder="ძიება სახელით, ელ-ფოსტით, კომპანიით…" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
        <a href="/admin/users.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
        <p class="p-4 mb-0 text-muted">მომხმარებლები ვერ მოიძებნა.</p>
        <?php else: ?>
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>სახელი</th>
                    <th>ელ-ფოსტა</th>
                    <th>კომპანია</th>
                    <th class="text-center">შეკვეთები</th>
                    <th>როლი</th>
                    <th>შეუერთდა</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td class="fw-semibold">
                    <?= htmlspecialchars($u['name']) ?>
                    <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                    <span class="badge bg-secondary ms-1">თქვენ</span>
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
                        <?= $u['role'] === 'admin' ? 'ადმინი' : 'მომხმარებელი' ?>
                    </span>
                </td>
                <td class="text-muted small"><?= date('d M, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/admin/user_edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form method="POST" onsubmit="return confirm('შეიცვალოს \"<?= addslashes(htmlspecialchars($u['name'])) ?>\" -ის როლი?')">
                            <input type="hidden" name="action" value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm btn-outline-<?= $u['role'] === 'admin' ? 'danger' : 'success' ?>">
                                <?= $u['role'] === 'admin' ? 'ადმინის გაუქმება' : 'ადმინად დანიშვნა' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
