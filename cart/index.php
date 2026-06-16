<?php
require_once '../includes/header.php';
requireLogin();

$uid = $_SESSION['user_id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $cartId = (int) $_POST['cart_id'];
        $qty    = (int) $_POST['quantity'];
        if ($qty > 0) {
            $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?")
                ->execute([$qty, $cartId, $uid]);
        } else {
            $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")
                ->execute([$cartId, $uid]);
        }
        redirect('/cart/index.php');
    }

    if ($action === 'remove') {
        $cartId = (int) $_POST['cart_id'];
        $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")
            ->execute([$cartId, $uid]);
        flash('success', 'Item removed from cart.');
        redirect('/cart/index.php');
    }

    if ($action === 'checkout') {
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $pdo->prepare("
            SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.price, p.stock, p.name
            FROM cart c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$uid]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            flash('danger', 'Your cart is empty.');
            redirect('/cart/index.php');
        }

        // Validate stock
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock']) {
                flash('danger', '"' . $item['name'] . '" only has ' . $item['stock'] . ' units in stock.');
                redirect('/cart/index.php');
            }
        }

        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO orders (user_id, total, notes) VALUES (?, ?, ?)")
                ->execute([$uid, $total, $notes ?: null]);
            $orderId = $pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $upd = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($cartItems as $item) {
                $ins->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                $upd->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$uid]);
            $pdo->commit();

            flash('success', 'Order #' . $orderId . ' placed successfully!');
            redirect('/orders/view.php?id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('danger', 'Checkout failed. Please try again.');
            redirect('/cart/index.php');
        }
    }
}

// Load cart
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock, p.unit
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    ORDER BY p.name
");
$stmt->execute([$uid]);
$cartItems = $stmt->fetchAll();

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-cart-fill me-2"></i>Your Cart</h2>
    <a href="/products/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Continue Shopping
    </a>
</div>

<?php if (empty($cartItems)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-cart-x display-3 d-block mb-3"></i>
        <p class="mb-0">Your cart is empty. <a href="/products/index.php">Browse products</a>.</p>
    </div>
</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center" style="width:130px">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="text-muted small">per <?= htmlspecialchars($item['unit']) ?></div>
                        </td>
                        <td>
                            <form method="POST" class="d-flex gap-1 justify-content-center">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>"
                                       min="0" max="<?= $item['stock'] ?>" class="form-control form-control-sm text-center" style="width:65px">
                                <button class="btn btn-sm btn-outline-secondary" title="Update">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                        <td class="text-end fw-semibold">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Remove">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Order Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span>$<?= number_format($total, 2) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                    <span>Total</span>
                    <span class="text-primary">$<?= number_format($total, 2) ?></span>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="checkout">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Order Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Special instructions, delivery notes…"></textarea>
                    </div>
                    <button class="btn btn-success w-100 py-2 fw-semibold">
                        <i class="bi bi-bag-check-fill me-2"></i>Place Order
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
