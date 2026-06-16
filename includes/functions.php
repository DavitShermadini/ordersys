<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: /index.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCartCount($pdo) {
    if (!isLoggedIn()) return 0;
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function statusBadge($status) {
    $colors = [
        'pending'    => 'warning',
        'processing' => 'info',
        'shipped'    => 'primary',
        'delivered'  => 'success',
        'cancelled'  => 'danger',
    ];
    $labels = [
        'pending'    => 'მოლოდინში',
        'processing' => 'მუშავდება',
        'shipped'    => 'გაიგზავნა',
        'delivered'  => 'მიწოდებულია',
        'cancelled'  => 'გაუქმებულია',
    ];
    $color = $colors[$status] ?? 'secondary';
    $label = $labels[$status] ?? htmlspecialchars($status);
    return "<span class='badge bg-$color'>$label</span>";
}
