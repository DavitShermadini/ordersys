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

$filterStatus = $_GET['status'] ?? '';
$viewId       = (int) ($_GET['view'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ─── ფასის განახლება ──────────────────────────────────────
    if ($action === 'update_price') {
        $itemId  = (int) ($_POST['item_id'] ?? 0);
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $newPrice = str_replace(',', '.', $_POST['adjusted_price'] ?? '');

        if ($itemId && $orderId && is_numeric($newPrice) && (float)$newPrice >= 0) {
            $adj = (float)$newPrice;

            // NULL means "same as original" — store NULL if unchanged to keep it clean
            $stmt = $pdo->prepare("SELECT price FROM order_items WHERE id = ?");
            $stmt->execute([$itemId]);
            $orig = (float) $stmt->fetchColumn();
            $storeAdj = (abs($adj - $orig) < 0.001) ? null : $adj;

            $pdo->prepare("UPDATE order_items SET adjusted_price = ? WHERE id = ?")
                ->execute([$storeAdj, $itemId]);

            // Recalculate order total
            $pdo->prepare("
                UPDATE orders SET total = (
                    SELECT SUM(COALESCE(oi.adjusted_price, oi.price) * oi.quantity)
                    FROM order_items oi WHERE oi.order_id = ?
                ) WHERE id = ?
            ")->execute([$orderId, $orderId]);

            flash('success', 'პოზიციის ფასი განახლდა.');
        } else {
            flash('danger', 'არასწორი მნიშვნელობა.');
        }
        $qs = http_build_query(array_filter(['view' => $orderId, 'status' => $filterStatus]));
        redirect('/admin/orders.php?' . $qs);
    }

    // ─── სტატუსის განახლება ──────────────────────────────────
    if (isset($_POST['order_id'], $_POST['status'])) {
        $allowed = array_keys($statusLabels);
        $status  = in_array($_POST['status'], $allowed) ? $_POST['status'] : null;
        if ($status) {
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
                ->execute([$status, (int) $_POST['order_id']]);
            flash('success', 'შეკვეთა #' . (int)$_POST['order_id'] . ' სტატუსი: ' . $statusLabels[$status]);
        }
        $qs = http_build_query(array_filter(['status' => $filterStatus, 'view' => $_GET['view'] ?? '']));
        redirect('/admin/orders.php' . ($qs ? '?' . $qs : ''));
    }
}

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
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS user_name, u.company,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o JOIN users u ON u.id = o.user_id
    $where
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<?php require_once 'partials/subnav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-list-ul me-2"></i>შეკვეთები</h2>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="/admin/orders.php" class="btn btn-sm <?= !$filterStatus ? 'btn-dark' : 'btn-outline-secondary' ?>">ყველა</a>
    <?php foreach ($statusLabels as $s => $label): ?>
    <a href="/admin/orders.php?status=<?= $s ?><?= $viewId ? '&view='.$viewId : '' ?>"
       class="btn btn-sm <?= $filterStatus === $s ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- ─── შეკვეთების სია ──────────────────────────────── -->
    <div class="<?= $viewOrder ? 'col-lg-5' : 'col-12' ?>">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <?php if (empty($orders)): ?>
                <p class="p-4 mb-0 text-muted">შეკვეთები ვერ მოიძებნა.</p>
                <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>მომხმარებელი</th>
                            <th class="d-none d-md-table-cell">პოზ.</th>
                            <th>სულ</th>
                            <th>სტატუსი</th>
                            <th class="d-none d-lg-table-cell">თარიღი</th>
                            <th></th>
                        </tr>
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
                        <td class="d-none d-md-table-cell"><?= $o['item_count'] ?></td>
                        <td>₾<?= number_format($o['total'], 2) ?></td>
                        <td><?= statusBadge($o['status']) ?></td>
                        <td class="text-muted small d-none d-lg-table-cell">
                            <?= date('d M, Y', strtotime($o['created_at'])) ?>
                        </td>
                        <td>
                            <a href="/admin/orders.php?view=<?= $o['id'] ?><?= $filterStatus ? '&status='.urlencode($filterStatus) : '' ?>"
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
    <!-- ─── შეკვეთის დეტალი ─────────────────────────────── -->
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">შეკვეთა #<?= $viewOrder['id'] ?></span>
                <a href="/admin/orders.php<?= $filterStatus ? '?status='.urlencode($filterStatus) : '' ?>"
                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
            <div class="card-body">

                <!-- მომხმარებლის ინფო -->
                <div class="mb-3 p-3 bg-light rounded small">
                    <div class="fw-semibold"><?= htmlspecialchars($viewOrder['user_name']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($viewOrder['user_email']) ?></div>
                    <?php if ($viewOrder['company']): ?>
                    <div class="text-muted"><?= htmlspecialchars($viewOrder['company']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- პოზიციების ცხრილი -->
                <div class="table-responsive mb-3">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>პროდუქტი</th>
                            <th class="text-center">რაოდ.</th>
                            <th class="text-end" style="min-width:140px">ფასი/ერთ.</th>
                            <th class="text-end">სულ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($viewItems as $item):
                        $origPrice = (float) $item['price'];
                        $adjPrice  = $item['adjusted_price'] !== null ? (float) $item['adjusted_price'] : null;
                        $effPrice  = $adjPrice ?? $origPrice;
                        $subtotal  = $effPrice * $item['quantity'];
                        $priceChanged = $adjPrice !== null && abs($adjPrice - $origPrice) >= 0.001;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name'] ?? 'წაშლილი') ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end" style="min-width:140px">
                            <!-- ── ფასის ჩვენება / რედაქტირება ── -->
                            <div id="pdisp-<?= $item['id'] ?>" class="d-flex align-items-center justify-content-end gap-2">
                                <div class="text-end lh-sm">
                                    <?php if ($priceChanged): ?>
                                    <div class="fw-semibold <?= $adjPrice > $origPrice ? 'text-danger' : 'text-success' ?>">
                                        ₾<?= number_format($adjPrice, 2) ?>
                                    </div>
                                    <small class="text-muted text-decoration-line-through">₾<?= number_format($origPrice, 2) ?></small>
                                    <?php else: ?>
                                    <span>₾<?= number_format($origPrice, 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <button type="button"
                                        class="btn btn-link btn-sm p-0 text-muted"
                                        onclick="editPrice(<?= $item['id'] ?>)"
                                        title="ფასის შეცვლა">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>
                            <!-- ── ინლაინ ფორმა ── -->
                            <form id="pform-<?= $item['id'] ?>" class="d-none mt-1" method="POST">
                                <input type="hidden" name="action"   value="update_price">
                                <input type="hidden" name="item_id"  value="<?= $item['id'] ?>">
                                <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₾</span>
                                    <input type="number" name="adjusted_price"
                                           class="form-control" step="0.01" min="0" style="width:72px"
                                           value="<?= number_format($adjPrice ?? $origPrice, 2, '.', '') ?>">
                                    <button class="btn btn-success" type="submit" title="შენახვა">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="editPrice(<?= $item['id'] ?>)" title="გაუქმება">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </form>
                        </td>
                        <td class="text-end fw-semibold">₾<?= number_format($subtotal, 2) ?></td>
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
                </div>

                <?php if ($viewOrder['notes']): ?>
                <div class="mb-3 p-2 bg-light rounded text-muted small">
                    <strong>შენიშვნა:</strong> <?= nl2br(htmlspecialchars($viewOrder['notes'])) ?>
                </div>
                <?php endif; ?>

                <!-- სტატუსის ფორმა -->
                <form method="POST" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                    <select name="status" class="form-select form-select-sm" style="max-width:200px">
                        <?php foreach ($statusLabels as $s => $label): ?>
                        <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary text-nowrap">სტატუსის განახლება</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function editPrice(id) {
    document.getElementById('pdisp-' + id).classList.toggle('d-none');
    document.getElementById('pform-' + id).classList.toggle('d-none');
}
</script>

<?php require_once '../includes/footer.php'; ?>
