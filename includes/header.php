<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$cartCount = isLoggedIn() ? getCartCount($pdo) : 0;
$flash = getFlash();
ob_start();
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OrderSys &mdash; B2B შეკვეთების მართვა</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ── მთავარი ნავბარი ─────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/index.php">
            <i class="bi bi-box-seam-fill me-1"></i>OrderSys
        </a>

        <?php if (isLoggedIn()): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!empty($_SESSION['user_company'])): ?>
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($_SESSION['user_company']) ?></span></li>
                        <?php endif; ?>
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/auth/logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>გასვლა
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <?php else: ?>
        <div class="ms-auto">
            <a class="btn btn-outline-light btn-sm px-3" href="/auth/login.php">შესვლა</a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<?php if (isLoggedIn() && !isAdmin()): ?>
<!-- ── მომხმარებლის სუბნავი ──────────────────────────────────── -->
<nav class="bg-white border-bottom shadow-sm">
    <div class="container">
        <ul class="nav nav-underline py-1 flex-nowrap overflow-auto">
            <li class="nav-item">
                <a class="nav-link px-3 py-2 <?= str_contains($_SERVER['PHP_SELF'] ?? '', '/products/') ? 'active fw-semibold' : 'text-dark' ?>"
                   href="/products/index.php">
                    <i class="bi bi-grid-fill me-1"></i>პროდუქტები
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 <?= str_contains($_SERVER['PHP_SELF'] ?? '', '/orders/') ? 'active fw-semibold' : 'text-dark' ?>"
                   href="/orders/index.php">
                    <i class="bi bi-list-ul me-1"></i>ჩემი შეკვეთები
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link px-3 py-2 position-relative <?= str_contains($_SERVER['PHP_SELF'] ?? '', '/cart/') ? 'active fw-semibold' : 'text-dark' ?>"
                   href="/cart/index.php">
                    <i class="bi bi-cart-fill me-1"></i>კალათა
                    <?php if ($cartCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-1"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>
</nav>
<?php endif; ?>

<div class="container py-4">
<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
