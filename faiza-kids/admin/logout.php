<?php
/**
 * Faiza Kids Concierge — Déconnexion
 */

require_once '../includes/auth.php';
logout();
header('Location: /admin/login');
exit;
