<?php
/**
 * Faiza Kids Concierge - Main Router
 * Parses the URL and dispatches to the correct handler.
 */

// Bootstrap
define('FK_ROOT', __DIR__);
define('FK_START', microtime(true));

// Load config if it exists, otherwise redirect to installer
$config_file = FK_ROOT . '/config.php';
if (!file_exists($config_file)) {
    // Only allow install.php
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!str_ends_with($uri, 'install.php')) {
        header('Location: /install.php');
        exit;
    }
}

if (file_exists($config_file)) {
    require_once $config_file;
}

// Parse the request URI
$request_uri  = $_SERVER['REQUEST_URI'] ?? '/';
$script_name  = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$path         = parse_url($request_uri, PHP_URL_PATH);

// Strip base path if app is in a subdirectory
if ($script_name && $script_name !== '/' && str_starts_with($path, $script_name)) {
    $path = substr($path, strlen($script_name));
}

$path = '/' . ltrim($path, '/');
$segments = array_values(array_filter(explode('/', trim($path, '/'))));

$seg0 = $segments[0] ?? '';
$seg1 = $segments[1] ?? '';
$seg2 = $segments[2] ?? '';
$seg3 = $segments[3] ?? '';

// ── QR scan tracker ──────────────────────────────────────────────────────────
// If source=qr-* parameter present, increment scan count
if (!empty($_GET['source']) && str_starts_with($_GET['source'], 'qr-') && file_exists($config_file)) {
    try {
        require_once FK_ROOT . '/includes/db.php';
        db_query("UPDATE qr_links SET scan_count = scan_count + 1 WHERE source_param = ?", [$_GET['source']]);
    } catch (Exception $e) { /* silent */ }
}

// ── Route Dispatch ───────────────────────────────────────────────────────────

// API routes
if ($seg0 === 'api') {
    $api_map = [
        'bookings'      => 'api/bookings.php',
        'hotels'        => 'api/hotels.php',
        'babysitters'   => 'api/babysitters.php',
        'settings'      => 'api/settings.php',
        'whatsapp'      => 'api/whatsapp.php',
        'payments'      => 'api/payments.php',
        'reports'       => 'api/reports.php',
        'upload'        => 'api/upload.php',
        'export'        => 'api/export.php',
        'notifications' => 'api/notifications.php',
        'search'        => 'api/search.php',
    ];
    $api_handler = $api_map[$seg1] ?? null;
    if ($api_handler && file_exists(FK_ROOT . '/' . $api_handler)) {
        require FK_ROOT . '/' . $api_handler;
        exit;
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
    exit;
}

// Admin routes
if ($seg0 === 'admin') {
    $admin_map = [
        ''               => 'admin/dashboard.php',
        'login'          => 'admin/login.php',
        'logout'         => 'admin/logout.php',
        'calendar'       => 'admin/calendar.php',
        'bookings'       => 'admin/bookings.php',
        'manual-booking' => 'admin/manual-booking.php',
        'availability'   => 'admin/availability.php',
        'hotels'         => 'admin/hotels.php',
        'babysitters'    => 'admin/babysitters.php',
        'whatsapp'       => 'admin/whatsapp.php',
        'payments'       => 'admin/payments.php',
        'reports'        => 'admin/reports.php',
        'qr'             => 'admin/qr.php',
        'settings'       => 'admin/settings.php',
    ];

    // Handle /admin/bookings/view/{id}
    if ($seg1 === 'bookings' && $seg2 === 'view' && !empty($seg3)) {
        $_GET['id'] = $seg3;
        $file = FK_ROOT . '/admin/booking-detail.php';
        if (file_exists($file)) { require $file; exit; }
    }

    $admin_page = $admin_map[$seg1] ?? null;
    if ($admin_page && file_exists(FK_ROOT . '/' . $admin_page)) {
        require FK_ROOT . '/' . $admin_page;
        exit;
    }

    // Fallback: admin dashboard
    $dashboard = FK_ROOT . '/admin/dashboard.php';
    if (file_exists($dashboard)) { require $dashboard; exit; }
}

// Public routes
if ($seg0 === 'hotel' && !empty($seg1)) {
    $_GET['slug'] = $seg1;
    $file = FK_ROOT . '/public/hotel.php';
    if (file_exists($file)) { require $file; exit; }
}

if ($seg0 === 'city') {
    $file = FK_ROOT . '/public/city.php';
    if (file_exists($file)) { require $file; exit; }
}

if ($seg0 === 'success' && !empty($seg1)) {
    $_GET['reference'] = $seg1;
    $file = FK_ROOT . '/public/success.php';
    if (file_exists($file)) { require $file; exit; }
}

if ($seg0 === 'payment-proof' && !empty($seg1)) {
    $_GET['token'] = $seg1;
    $file = FK_ROOT . '/public/payment-proof.php';
    if (file_exists($file)) { require $file; exit; }
}

// Root redirect
if ($path === '/' || $path === '') {
    header('Location: /admin');
    exit;
}

// 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 – Page introuvable | Faiza Kids</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#F8FAF9;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#1A2E24}
.box{text-align:center;padding:60px 40px}
.circle{width:80px;height:80px;border-radius:50%;background:#0D2B1D;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;color:#52B788;font-size:32px;font-weight:700}
h1{font-size:24px;font-weight:700;margin-bottom:12px}
p{color:#6B7A72;margin-bottom:24px}
a{display:inline-block;background:#2D6A4F;color:#fff;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600}
a:hover{background:#40916C}
</style>
</head>
<body>
<div class="box">
  <div class="circle">F</div>
  <h1>Page introuvable</h1>
  <p>La page que vous cherchez n'existe pas ou a été déplacée.</p>
  <a href="/admin">Retour au tableau de bord</a>
</div>
</body>
</html>
