<?php
/**
 * Faiza Kids — admin directory fallback
 * Redirects /admin/ to dashboard when URL rewriting is unavailable.
 */
require_once __DIR__ . '/../includes/auth.php';
if (!is_logged_in()) {
    header('Location: /admin/login.php');
    exit;
}
header('Location: /admin/dashboard.php');
exit;
