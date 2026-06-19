<?php
/**
 * PHP built-in server router.
 * გაუშვით:  php -S localhost:8080 router.php
 *
 * Handles /api/products/1 → api/products.php?id=1
 * და სხვა clean URL pattern-ები.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static files (CSS, JS, images, fonts) — serve directly
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri) && !str_ends_with($uri, '.php')) {
    return false;
}

// ─── API route table ──────────────────────────────────────────────────────────
// [pattern, file, ?param_name]
$routes = [
    ['#^/api/auth/login/?$#',          'api/auth/login.php'],
    ['#^/api/auth/register/?$#',       'api/auth/register.php'],
    ['#^/api/auth/me/?$#',             'api/auth/me.php'],

    ['#^/api/products/(\d+)/?$#',      'api/products.php',         'id'],
    ['#^/api/products/?$#',            'api/products.php'],

    ['#^/api/categories/(\d+)/?$#',    'api/categories.php',       'id'],
    ['#^/api/categories/?$#',          'api/categories.php'],

    ['#^/api/cart/?$#',                'api/cart.php'],

    ['#^/api/orders/(\d+)/?$#',        'api/orders.php',           'id'],
    ['#^/api/orders/?$#',              'api/orders.php'],

    ['#^/api/admin/products/?$#',      'api/admin/products.php'],
    ['#^/api/admin/orders/?$#',        'api/admin/orders.php'],
    ['#^/api/admin/users/?$#',         'api/admin/users.php'],
];

foreach ($routes as $route) {
    [$pattern, $file] = $route;
    $param = $route[2] ?? null;

    if (preg_match($pattern, $uri, $m)) {
        if ($param && isset($m[1])) {
            $_GET[$param] = $m[1];
        }
        require __DIR__ . '/' . $file;
        exit;
    }
}

// ─── PHP web UI files ─────────────────────────────────────────────────────────
$path = __DIR__ . $uri;

if (is_dir($path)) {
    // Directory: look for index.html first (e.g. /api/docs/), then index.php
    $htmlIndex = rtrim($path, '/\\') . '/index.html';
    $phpIndex  = rtrim($path, '/\\') . '/index.php';
    if (file_exists($htmlIndex)) { return false; } // built-in server serves it
    if (file_exists($phpIndex))  { require $phpIndex; exit; }
}

if (str_ends_with($uri, '.php') && file_exists($path)) {
    require $path;
    exit;
}

// Fallback
http_response_code(404);
echo json_encode(['error' => 'Not found']);
