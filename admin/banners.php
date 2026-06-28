<?php
require_once '../includes/header.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['banner_id'] ?? 0);

    if ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $file = __DIR__ . '/../uploads/banners/' . $row['image'];
            if (file_exists($file)) @unlink($file);
            $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
            flash('success', 'ბანერი წაიშალა.');
        }
    }

    if ($action === 'toggle' && $id) {
        $pdo->prepare("UPDATE banners SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }

    if (in_array($action, ['move_up', 'move_down']) && $id) {
        $cur = $pdo->prepare("SELECT sort_order FROM banners WHERE id = ?");
        $cur->execute([$id]);
        $curOrder = (int) $cur->fetchColumn();

        if ($action === 'move_up') {
            $adj = $pdo->prepare("SELECT id, sort_order FROM banners WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
        } else {
            $adj = $pdo->prepare("SELECT id, sort_order FROM banners WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
        }
        $adj->execute([$curOrder]);
        $adjRow = $adj->fetch();

        if ($adjRow) {
            $pdo->prepare("UPDATE banners SET sort_order = ? WHERE id = ?")->execute([$adjRow['sort_order'], $id]);
            $pdo->prepare("UPDATE banners SET sort_order = ? WHERE id = ?")->execute([$curOrder, $adjRow['id']]);
        }
    }

    redirect('/admin/banners.php');
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY sort_order, id")->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-images me-2"></i>სლაიდერ ბანერები</h2>
    <a href="/admin/banner_edit.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>ბანერის დამატება
    </a>
</div>

<?php if (empty($banners)): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-images display-3 d-block mb-3 opacity-25"></i>
        <p class="mb-3">ბანერები ჯერ არ არის დამატებული.</p>
        <a href="/admin/banner_edit.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>პირველი ბანერის დამატება
        </a>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:90px">ფოტო</th>
                    <th>სათაური / ქვესათაური</th>
                    <th class="text-center" style="width:140px">სტატუსი</th>
                    <th class="text-center" style="width:90px">რიგი</th>
                    <th style="width:90px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($banners as $b): ?>
            <tr>
                <td>
                    <img src="/uploads/banners/<?= htmlspecialchars($b['image']) ?>"
                         alt="" class="rounded border"
                         style="width:80px;height:48px;object-fit:cover">
                </td>
                <td>
                    <?php if ($b['title']): ?>
                    <div class="fw-semibold"><?= htmlspecialchars($b['title']) ?></div>
                    <?php endif; ?>
                    <?php if ($b['subtitle']): ?>
                    <div class="text-muted small"><?= htmlspecialchars($b['subtitle']) ?></div>
                    <?php endif; ?>
                    <?php if (!$b['title'] && !$b['subtitle']): ?>
                    <span class="text-muted small fst-italic">— სათაური არ აქვს —</span>
                    <?php endif; ?>
                    <?php if ($b['link_url']): ?>
                    <div class="small text-primary mt-1">
                        <i class="bi bi-link-45deg"></i>
                        <?= htmlspecialchars($b['link_url']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <form method="POST">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                        <button type="submit"
                                class="btn btn-sm <?= $b['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                            <i class="bi bi-<?= $b['is_active'] ? 'eye-fill' : 'eye-slash' ?> me-1"></i>
                            <?= $b['is_active'] ? 'ჩართული' : 'გამორთული' ?>
                        </button>
                    </form>
                </td>
                <td>
                    <div class="d-flex gap-1 justify-content-center">
                        <form method="POST">
                            <input type="hidden" name="action" value="move_up">
                            <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="ზემოთ">
                                <i class="bi bi-chevron-up"></i>
                            </button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="action" value="move_down">
                            <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="ქვემოთ">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </form>
                    </div>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/admin/banner_edit.php?id=<?= $b['id'] ?>"
                           class="btn btn-sm btn-outline-secondary" title="რედაქტირება">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              onsubmit="return confirm('ბანერი წაიშალოს?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="წაშლა">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
