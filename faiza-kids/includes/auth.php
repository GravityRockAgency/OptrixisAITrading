<?php
/**
 * Faiza Kids Concierge - Authentication & Session Management
 */

if (!defined('APP_SECRET')) {
    require_once dirname(__DIR__) . '/config.php';
}

require_once __DIR__ . '/db.php';

// Start session with security settings if not already started
if (session_status() === PHP_SESSION_NONE) {
    $session_lifetime = 7200; // 2 hours
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}

/**
 * Check whether an admin is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_username']);
}

/**
 * Redirect to login page if not authenticated
 */
function require_login(): void {
    if (!is_logged_in()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php');
        header('Location: /admin/login.php?redirect=' . $redirect);
        exit;
    }
}

/**
 * Attempt to log in an admin
 */
function login(string $username, string $password) {
    $admin = db_fetch(
        "SELECT * FROM admins WHERE username = ? LIMIT 1",
        [trim($username)]
    );

    if (!$admin) {
        return false;
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    // Successful login
    session_regenerate_id(true);
    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name']     = $admin['full_name'] ?? $admin['username'];
    $_SESSION['admin_email']    = $admin['email'] ?? '';
    $_SESSION['_created']       = time();
    $_SESSION['_ip']            = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['_ua']            = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);

    // Update last login timestamp
    db_update('admins', ['last_login' => date('Y-m-d H:i:s')], ['id' => $admin['id']]);

    return $admin;
}

/**
 * Destroy the current session and log the admin out
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Return current admin data from DB (cached in session)
 */
function get_admin(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    if (empty($_SESSION['_admin_data'])) {
        $admin = db_fetch(
            "SELECT id, username, email, full_name, avatar, last_login, created_at FROM admins WHERE id = ?",
            [$_SESSION['admin_id']]
        );
        $_SESSION['_admin_data'] = $admin ?: null;
    }
    return $_SESSION['_admin_data'];
}

// ── CSRF Protection ─────────────────────────────────────────────

function generate_csrf(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf(string $token): bool {
    if (empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(generate_csrf(), ENT_QUOTES) . '">';
}

function require_csrf(): void {
    $token = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verify_csrf($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
        exit;
    }
}
