<nav class="nav nav-pills mb-4 p-2 bg-light rounded shadow-sm">
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : 'text-dark' ?>"
       href="/admin/index.php"><i class="bi bi-speedometer2 me-1"></i>მთავარი</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : 'text-dark' ?>"
       href="/admin/orders.php"><i class="bi bi-list-ul me-1"></i>შეკვეთები</a>
    <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['products.php','product_edit.php']) ? 'active' : 'text-dark' ?>"
       href="/admin/products.php"><i class="bi bi-box-seam me-1"></i>პროდუქტები</a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : 'text-dark' ?>"
       href="/admin/users.php"><i class="bi bi-people-fill me-1"></i>მომხმარებლები</a>
</nav>
