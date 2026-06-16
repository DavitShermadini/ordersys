<?php
require_once '../includes/header.php';
requireLogin();

$id  = (int) ($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $uid]);
$order = $stmt->fetch();

if (!$order) {
    flash('danger', 'შეკვეთა ვერ მოიძებნა.');
    redirect('/orders/index.php');
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.name AS product_name, p.unit
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/orders/index.php" class="text-muted small d-block mb-1">
            <i class="bi bi-arrow-left me-1"></i>შეკვეთებზე დაბრუნება
        </a>
        <h2 class="mb-0">შეკვეთა #<?= $order['id'] ?></h2>
    </div>
    <?= statusBadge($order['status']) ?>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">შეკვეთის პოზიციები</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>პროდუქტი</th>
                            <th class="text-center">რაოდ.</th>
                            <th class="text-end">ფასი</th>
                            <th class="text-end">სულ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name'] ?? 'წაშლილი პროდუქტი') ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-end">₾<?= number_format($item['price'], 2) ?></td>
                        <td class="text-end fw-semibold">₾<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">შეკვეთის ჯამი</td>
                            <td class="text-end fw-bold fs-5 text-primary">₾<?= number_format($order['total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">შეკვეთის დეტალები</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">შეკვეთა #</dt>
                    <dd class="col-7"><?= $order['id'] ?></dd>

                    <dt class="col-5">გაფორმდა</dt>
                    <dd class="col-7"><?= date('d M, Y', strtotime($order['created_at'])) ?></dd>

                    <dt class="col-5">განახლდა</dt>
                    <dd class="col-7"><?= date('d M, Y', strtotime($order['updated_at'])) ?></dd>

                    <dt class="col-5">სტატუსი</dt>
                    <dd class="col-7"><?= statusBadge($order['status']) ?></dd>
                </dl>
            </div>
        </div>

        <?php if (!empty($order['notes'])): ?>
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">შენიშვნები</div>
            <div class="card-body">
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
