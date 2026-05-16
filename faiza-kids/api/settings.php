<?php
/**
 * Faiza Kids Concierge — Settings API
 * Manages application settings, SMTP testing, backup and data reset
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

function json_success(array $data = []): void {
    echo json_encode(['success' => true, ...$data]);
    exit;
}

function json_error(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// ── Route dispatcher ──────────────────────────────────────────────────────────
match (true) {
    $method === 'GET'  && $action === 'get'              => action_get(),
    $method === 'POST' && $action === 'save'             => action_save(),
    $method === 'POST' && $action === 'test_smtp'        => action_test_smtp(),
    $method === 'GET'  && $action === 'export_backup'    => action_export_backup(),
    $method === 'POST' && $action === 'reset_test_data'  => action_reset_test_data(),
    default                                              => json_error('Action non reconnue', 404),
};

// ── GET action=get ────────────────────────────────────────────────────────────
function action_get(): void {
    $rows = db_fetch_all("SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC");

    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    json_success(['settings' => $settings]);
}

// ── POST action=save ──────────────────────────────────────────────────────────
function action_save(): void {
    global $input;

    if (empty($input) || !is_array($input)) {
        json_error('Aucun paramètre fourni');
    }

    // Blacklisted keys that cannot be changed via API
    $blacklisted = ['install_token', 'admin_password'];

    $saved = [];
    $skipped = [];

    foreach ($input as $key => $value) {
        $key = (string)$key;
        if (in_array($key, $blacklisted, true)) {
            $skipped[] = $key;
            continue;
        }
        // Only accept scalar values
        if (!is_scalar($value) && $value !== null) {
            $skipped[] = $key;
            continue;
        }
        set_setting($key, (string)($value ?? ''));
        $saved[] = $key;
    }

    log_activity('settings_saved', 'Paramètres mis à jour : ' . implode(', ', $saved));

    json_success([
        'saved'   => $saved,
        'skipped' => $skipped,
        'message' => count($saved) . ' paramètre(s) enregistré(s)',
    ]);
}

// ── POST action=test_smtp ─────────────────────────────────────────────────────
function action_test_smtp(): void {
    global $input;

    // Read SMTP settings from input or fall back to stored settings
    $smtp_host = $input['smtp_host'] ?? get_setting('smtp_host', '');
    $smtp_port = (int)($input['smtp_port'] ?? get_setting('smtp_port', '587'));
    $smtp_user = $input['smtp_user'] ?? get_setting('smtp_user', '');
    $smtp_pass = $input['smtp_pass'] ?? get_setting('smtp_pass', '');
    $smtp_from = $input['smtp_from'] ?? get_setting('smtp_from_email', $smtp_user);
    $smtp_enc  = $input['smtp_encryption'] ?? get_setting('smtp_encryption', 'tls');
    $test_to   = $input['test_email'] ?? get_admin()['email'] ?? '';

    if (!$smtp_host) json_error('Hôte SMTP non configuré');
    if (!$smtp_user) json_error('Utilisateur SMTP non configuré');
    if (!$test_to)   json_error('Adresse email de test manquante');

    // Test TCP connection first
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $scheme = ($smtp_enc === 'ssl') ? 'ssl://' : '';
    $fp = @stream_socket_client(
        $scheme . $smtp_host . ':' . $smtp_port,
        $errno,
        $errstr,
        10,
        STREAM_CLIENT_CONNECT,
        $ctx
    );

    if (!$fp) {
        json_error("Impossible de se connecter au serveur SMTP ($smtp_host:$smtp_port) : $errstr");
    }

    // Read banner
    $banner = fgets($fp, 1024);
    if (!str_starts_with(trim($banner), '220')) {
        fclose($fp);
        json_error('Réponse inattendue du serveur SMTP : ' . trim($banner));
    }

    // EHLO
    fputs($fp, "EHLO faizakids.local\r\n");
    $ehlo = '';
    while ($line = fgets($fp, 1024)) {
        $ehlo .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }

    // STARTTLS if needed
    if ($smtp_enc === 'tls') {
        fputs($fp, "STARTTLS\r\n");
        $tls_resp = fgets($fp, 1024);
        if (!str_starts_with(trim($tls_resp), '220')) {
            fclose($fp);
            json_error('STARTTLS refusé par le serveur : ' . trim($tls_resp));
        }
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        fputs($fp, "EHLO faizakids.local\r\n");
        while ($line = fgets($fp, 1024)) {
            if (isset($line[3]) && $line[3] === ' ') break;
        }
    }

    // AUTH LOGIN
    fputs($fp, "AUTH LOGIN\r\n");
    $auth_resp = fgets($fp, 1024);
    if (!str_starts_with(trim($auth_resp), '334')) {
        fclose($fp);
        json_error('Authentification SMTP refusée : ' . trim($auth_resp));
    }

    fputs($fp, base64_encode($smtp_user) . "\r\n");
    $user_resp = fgets($fp, 1024);
    if (!str_starts_with(trim($user_resp), '334')) {
        fclose($fp);
        json_error('Identifiant SMTP refusé');
    }

    fputs($fp, base64_encode($smtp_pass) . "\r\n");
    $pass_resp = fgets($fp, 1024);
    if (!str_starts_with(trim($pass_resp), '235')) {
        fclose($fp);
        json_error('Mot de passe SMTP incorrect');
    }

    // Send test email
    $company = get_setting('company_name', 'Faiza Kids Concierge');
    $subject  = '=?UTF-8?B?' . base64_encode('Test SMTP — ' . $company) . '?=';
    $body     = "Ceci est un email de test envoyé depuis $company.\r\nDate : " . date('d/m/Y H:i:s');

    fputs($fp, "MAIL FROM:<$smtp_from>\r\n");
    fgets($fp, 1024);
    fputs($fp, "RCPT TO:<$test_to>\r\n");
    fgets($fp, 1024);
    fputs($fp, "DATA\r\n");
    fgets($fp, 1024);

    fputs($fp, "From: $company <$smtp_from>\r\n");
    fputs($fp, "To: $test_to\r\n");
    fputs($fp, "Subject: $subject\r\n");
    fputs($fp, "MIME-Version: 1.0\r\n");
    fputs($fp, "Content-Type: text/plain; charset=UTF-8\r\n");
    fputs($fp, "\r\n");
    fputs($fp, $body . "\r\n");
    fputs($fp, ".\r\n");
    $data_resp = fgets($fp, 1024);

    fputs($fp, "QUIT\r\n");
    fclose($fp);

    if (!str_starts_with(trim($data_resp), '250')) {
        json_error('Email envoyé mais serveur a retourné : ' . trim($data_resp));
    }

    log_activity('smtp_test', "Test SMTP réussi → $test_to");

    json_success(['message' => "Email de test envoyé avec succès à $test_to"]);
}

// ── GET action=export_backup ──────────────────────────────────────────────────
function action_export_backup(): void {
    $data = [
        'export_date'    => date('Y-m-d H:i:s'),
        'version'        => '1.0',
        'settings'       => [],
        'hotels'         => [],
        'babysitters'    => [],
        'whatsapp_templates' => [],
        'bookings'       => [],
        'booking_children'   => [],
        'payment_proofs' => [],
        'notifications'  => [],
    ];

    // Settings
    $rows = db_fetch_all("SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC");
    foreach ($rows as $r) {
        $data['settings'][$r['setting_key']] = $r['setting_value'];
    }

    // Hotels
    $data['hotels'] = db_fetch_all("SELECT * FROM hotels ORDER BY id ASC");

    // Babysitters (exclude nothing sensitive here since this is admin export)
    $data['babysitters'] = db_fetch_all("SELECT * FROM babysitters ORDER BY id ASC");

    // Babysitter hotel assignments
    $data['babysitter_hotels'] = db_fetch_all("SELECT * FROM babysitter_hotels ORDER BY id ASC");

    // WhatsApp templates
    $data['whatsapp_templates'] = db_fetch_all("SELECT * FROM whatsapp_templates ORDER BY id ASC");

    // Bookings
    $data['bookings'] = db_fetch_all("SELECT * FROM bookings ORDER BY id ASC");

    // Booking children
    $data['booking_children'] = db_fetch_all("SELECT * FROM booking_children ORDER BY id ASC");

    // Payment proofs
    $data['payment_proofs'] = db_fetch_all("SELECT * FROM payment_proofs ORDER BY id ASC");

    // Activity log (last 1000 entries to keep file size reasonable)
    $data['activity_logs'] = db_fetch_all(
        "SELECT * FROM activity_logs ORDER BY id DESC LIMIT 1000"
    );

    $filename = 'faizakids_backup_' . date('Y-m-d_His') . '.json';

    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    log_activity('backup_exported', 'Export de sauvegarde généré');

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── POST action=reset_test_data ───────────────────────────────────────────────
function action_reset_test_data(): void {
    global $input;

    $confirmation = trim($input['confirmation'] ?? '');
    $expected     = 'RESET_TEST_DATA';

    if ($confirmation !== $expected) {
        json_error("Code de confirmation invalide. Entrez exactement : $expected");
    }

    // Safety: only allow in development or with explicit flag
    $env = defined('APP_ENV') ? APP_ENV : get_setting('app_env', 'production');
    $force = !empty($input['force']) && $input['force'] === true;

    if ($env === 'production' && !$force) {
        json_error('Réinitialisation non autorisée en environnement de production. Ajoutez "force": true pour forcer.');
    }

    db_begin();
    try {
        // Delete in FK-safe order
        db_query("DELETE FROM payment_proofs");
        db_query("DELETE FROM booking_children");
        db_query("DELETE FROM whatsapp_logs");
        db_query("DELETE FROM notifications");
        db_query("DELETE FROM bookings");
        db_query("DELETE FROM activity_logs");

        // Reset auto-increment
        db_query("ALTER TABLE bookings         AUTO_INCREMENT = 1");
        db_query("ALTER TABLE booking_children AUTO_INCREMENT = 1");
        db_query("ALTER TABLE payment_proofs   AUTO_INCREMENT = 1");
        db_query("ALTER TABLE whatsapp_logs    AUTO_INCREMENT = 1");
        db_query("ALTER TABLE notifications    AUTO_INCREMENT = 1");
        db_query("ALTER TABLE activity_logs    AUTO_INCREMENT = 1");

        db_commit();
    } catch (Exception $e) {
        db_rollback();
        error_log('reset_test_data error: ' . $e->getMessage());
        json_error('Erreur lors de la réinitialisation : ' . $e->getMessage());
    }

    // Log after tables are cleared (fresh entry)
    log_activity('data_reset', 'Données de test réinitialisées par ' . ($_SESSION['admin_username'] ?? 'admin'));

    json_success([
        'message' => 'Données de test supprimées. La configuration, les hôtels et les templates ont été conservés.',
        'tables_cleared' => [
            'bookings', 'booking_children', 'payment_proofs',
            'whatsapp_logs', 'notifications', 'activity_logs',
        ],
    ]);
}
