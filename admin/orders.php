<?php
require_once '../includes/header.php';
requireAdmin();

$statusLabels = [
    'pending'    => 'მოლოდინში',
    'processing' => 'მუშავდება',
    'shipped'    => 'გაიგზავნა',
    'delivered'  => 'მიწოდებულია',
    'cancelled'  => 'გაუქმებულია',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $allowed = array_keys($statusLabels);
    $status  = in_array($_POST['status'], $allowed) ? $_POST['status'] : null;
    if ($status) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$status, (int) $_POST['order_id']]);
        flash('success', 'შეკვეთა #' . (int)$_POST['order_id'] . ' სტატუსი განახლდა: ' . $statusLabels[$status] . '.');
    }
    $qs = http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'view' => $_GET['view'] ?? '']));
    redirect('/admin/orders.php' . ($qs ? '?' . $qs : ''));
}

$filterStatus = $_GET['status'] ?? '';
$viewId       = (int) ($_GET['view'] ?? 0);

$viewOrder = null;
$viewItems = [];
if ($viewId) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email, u.company
        FROM orders o JOIN users u ON u.id = o.user_id
        WHERE o.id = ?
    ");
    $stmt->execute([$viewId]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.unit
            FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$viewId]);
        $viewItems = $stmt->fetchAll();
    }
}

$params = [];
$where  = '';
if ($filterStatus) {
    $where    = 'WHERE o.status = ?';
    $params[] = $filterStatus;
}
$orders = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.company,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o JOIN users u ON u.id = o.user_id
    $where
    ORDER BY o.created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-list-ul me-2"></i>შეკვეთები</h2>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="/admin/orders.php" class="btn btn-sm <?= !$filterStatus ? 'btn-dark' : 'btn-outline-secondary' ?>">ყველა</a>
    <?php foreach ($statusLabels as $s => $label): ?>
    <a href="/admin/orders.php?status=<?= $s ?>"
       class="btn btn-sm <?= $filterStatus === $s ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="<?= $viewOrder ? 'col-lg-6' : 'col-12' ?>">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                <p class="p-4 mb-0 text-muted">შეკვეთები ვერ მოიძებნა.</p>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>#</th><th>მომხმარებელი</th><th>პოზ.</th><th>სულ</th><th>სტატუსი</th><th>თარიღი</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr class="<?= $viewId == $o['id'] ? 'table-active' : '' ?>">
                        <td class="fw-bold">#<?= $o['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($o['user_name']) ?></div>
                            <?php if ($o['company']): ?>
                            <div class="text-muted small"><?= htmlspecialchars($o['company']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= $o['item_count'] ?></td>
                        <td>₾<?= number_format($o['total'], 2) ?></td>
                        <td><?= statusBadge($o['status']) ?></td>
                        <td class="text-muted small"><?= date('d M, Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <a href="/admin/orders.php?view=<?= $o['id'] ?><?= $filterStatus ? '&status=' . urlencode($filterStatus) : '' ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($viewOrder): ?>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">შეკვეთა #<?= $viewOrder['id'] ?></span>
                <a href="/admin/orders.php<?= $filterStatus ? '?status=' . urlencode($filterStatus) : '' ?>"
                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
            <div class="card-body">
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="fw-semibold"><?= htmlspecialchars($viewOrder['user_name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($viewOrder['user_email']) ?></div>
                    <?php if ($viewOrder['company']): ?>
                    <div class="text-muted small"><?= htmlspecialchars($viewOrder['company']) ?></div>
                    <?php endif; ?>
                </div>

                <table class="table table-sm mb-3">
                    <thead class="table-light">
                        <tr><th>პროდუქტი</th><th class="text-center">რაოდ.</th><th class="text-end">ფასი</th><th class="text-end">სულ</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($viewItems as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name'] ?? 'წაშლილი') ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end">₾<?= number_format($item['price'], 2) ?></td>
                        <td class="text-end">₾<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">სულ</td>
                            <td class="text-end fw-bold text-primary">₾<?= number_format($viewOrder['total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($viewOrder['notes']): ?>
                <div class="mb-3 p-2 bg-light rounded text-muted small">
                    <strong>შენიშვნები:</strong> <?= nl2br(htmlspecialchars($viewOrder['notes'])) ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                    <select name="status" class="form-select form-select-sm">
                        <?php foreach ($statusLabels as $s => $label): ?>
                        <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary text-nowrap">სტატუსის განახლება</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
