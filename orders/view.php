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

// Check if any prices were adjusted
$hasAdjusted = array_filter($items, fn($i) =>
    $i['adjusted_price'] !== null && abs((float)$i['adjusted_price'] - (float)$i['price']) >= 0.001
);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="/orders/index.php" class="text-muted small d-block mb-1">
            <i class="bi bi-arrow-left me-1"></i>შეკვეთებზე დაბრუნება
        </a>
        <h2 class="mb-0">შეკვეთა #<?= $order['id'] ?></h2>
    </div>
    <?= statusBadge($order['status']) ?>
</div>

<?php if ($hasAdjusted): ?>
<div class="alert alert-info py-2 small">
    <i class="bi bi-info-circle-fill me-1"></i>
    ადმინმა ზოგი პოზიციის ფასი შეცვალა.
    <span class="text-success fw-semibold">მწვანე</span> = გაიაფდა,
    <span class="text-danger fw-semibold">წითელი</span> = გაძვირდა.
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">შეკვეთის პოზიციები</div>
            <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>პროდუქტი</th>
                        <th class="text-center">რაოდ.</th>
                        <th class="text-end">ფასი/ერთ.</th>
                        <th class="text-end">სულ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item):
                    $origPrice = (float) $item['price'];
                    $adjPrice  = $item['adjusted_price'] !== null ? (float) $item['adjusted_price'] : null;
                    $effPrice  = $adjPrice ?? $origPrice;
                    $subtotal  = $effPrice * $item['quantity'];
                    $priceChanged = $adjPrice !== null && abs($adjPrice - $origPrice) >= 0.001;
                    $isUp = $priceChanged && $adjPrice > $origPrice;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name'] ?? 'წაშლილი პროდუქტი') ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end">
                        <?php if ($priceChanged): ?>
                        <div class="lh-sm">
                            <div class="fw-semibold <?= $isUp ? 'text-danger' : 'text-success' ?>">
                                ₾<?= number_format($adjPrice, 2) ?>
                            </div>
                            <small class="text-muted text-decoration-line-through">
                                ₾<?= number_format($origPrice, 2) ?>
                            </small>
                        </div>
                        <?php else: ?>
                        ₾<?= number_format($origPrice, 2) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold">
                        <?php if ($priceChanged): ?>
                        <span class="<?= $isUp ? 'text-danger' : 'text-success' ?>">
                            ₾<?= number_format($subtotal, 2) ?>
                        </span>
                        <?php else: ?>
                        ₾<?= number_format($subtotal, 2) ?>
                        <?php endif; ?>
                    </td>
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
                <dl class="row mb-0 small">
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
            <div class="card-body small text-muted">
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
