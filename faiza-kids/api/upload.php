<?php
/**
 * Faiza Kids Concierge — Upload API
 * Handles all file uploads (logos, images, payment proofs)
 *
 * NOTE: The proof upload action is PUBLIC (no login required) and validates via secure_token.
 * All other upload actions require admin login.
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── Public routes (no login) ──────────────────────────────────────────────────
if ($action === 'proof') {
    header('Content-Type: application/json; charset=UTF-8');
    handle_proof_upload();
    exit;
}

// ── All other routes require admin login ──────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

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
match ($action) {
    'logo'        => handle_logo_upload(),
    'hotel_logo'  => handle_hotel_logo_upload(),
    'login_image' => handle_login_image_upload(),
    default       => json_error('Action non reconnue', 404),
};

// ─────────────────────────────────────────────────────────────────────────────
// Shared upload helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Get max upload size in bytes (from settings or PHP defaults).
 */
function get_max_size(): int {
    $setting_mb = (int) get_setting('upload_max_size_mb', '5');
    if ($setting_mb > 0) {
        return $setting_mb * 1048576;
    }
    return 5 * 1048576; // 5 MB default
}

/**
 * Validate and move an uploaded file, returning structured result.
 */
function process_upload(
    string $field,
    string $dest_dir,
    array  $allowed_mimes,
    string $prefix,
    ?int   $entity_id = null
): array {
    if (empty($_FILES[$field])) {
        return ['success' => false, 'error' => 'Aucun fichier reçu (champ : ' . $field . ')'];
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err_map = [
            UPLOAD_ERR_INI_SIZE   => 'Le fichier dépasse la taille maximum du serveur.',
            UPLOAD_ERR_FORM_SIZE  => 'Le fichier dépasse la limite du formulaire.',
            UPLOAD_ERR_PARTIAL    => 'Upload partiel, veuillez réessayer.',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier sélectionné.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire introuvable.',
            UPLOAD_ERR_CANT_WRITE => 'Écriture impossible sur le disque.',
            UPLOAD_ERR_EXTENSION  => 'Upload bloqué par une extension PHP.',
        ];
        return ['success' => false, 'error' => $err_map[$file['error']] ?? 'Erreur upload inconnue.'];
    }

    $max_size = get_max_size();
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max ' . round($max_size / 1048576, 1) . ' Mo).'];
    }

    // Check MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed_mimes, true)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé (' . $mime . '). Types acceptés : ' . implode(', ', $allowed_mimes)];
    }

    // Extension mapping
    $ext_map = [
        'image/jpeg'       => 'jpg',
        'image/png'        => 'png',
        'image/webp'       => 'webp',
        'image/svg+xml'    => 'svg',
        'application/pdf'  => 'pdf',
    ];
    $ext = $ext_map[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Safe filename
    $id_part  = $entity_id ? $entity_id . '_' : '';
    $filename = $prefix . '_' . $id_part . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!is_dir($dest_dir)) {
        if (!mkdir($dest_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Impossible de créer le dossier de destination.'];
        }
    }

    $dest = rtrim($dest_dir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'error' => 'Déplacement du fichier impossible.'];
    }

    return [
        'success'  => true,
        'filename' => $filename,
        'path'     => $dest,
        'mime'     => $mime,
        'size'     => $file['size'],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Admin upload actions
// ─────────────────────────────────────────────────────────────────────────────

/**
 * POST action=logo — Upload the company/application logo
 */
function handle_logo_upload(): void {
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $dest_dir      = dirname(__DIR__) . '/uploads/logos';

    $result = process_upload('logo', $dest_dir, $allowed_mimes, 'company', null);
    if (!$result['success']) {
        json_error($result['error']);
    }

    $logo_path = 'uploads/logos/' . $result['filename'];

    // Delete old logo
    $old_path = get_setting('company_logo', '');
    if ($old_path) {
        $old_file = dirname(__DIR__) . '/' . ltrim($old_path, '/');
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }

    set_setting('company_logo', $logo_path);

    log_activity('logo_uploaded', 'Logo société mis à jour : ' . $result['filename']);

    json_success([
        'path'     => $logo_path,
        'url'      => (defined('BASE_URL') ? BASE_URL : '') . '/' . $logo_path,
        'filename' => $result['filename'],
        'message'  => 'Logo mis à jour avec succès',
    ]);
}

/**
 * POST action=hotel_logo&hotel_id=X — Upload a hotel logo
 */
function handle_hotel_logo_upload(): void {
    $hotel_id = (int)($_GET['hotel_id'] ?? $_POST['hotel_id'] ?? 0);
    if (!$hotel_id) json_error('Identifiant hôtel manquant');

    $hotel = db_fetch("SELECT id, name, logo FROM hotels WHERE id = ? LIMIT 1", [$hotel_id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $dest_dir      = dirname(__DIR__) . '/uploads/logos';

    $result = process_upload('logo', $dest_dir, $allowed_mimes, 'hotel', $hotel_id);
    if (!$result['success']) {
        json_error($result['error']);
    }

    $logo_path = 'uploads/logos/' . $result['filename'];

    // Delete old hotel logo
    if (!empty($hotel['logo'])) {
        $old_file = dirname(__DIR__) . '/' . ltrim($hotel['logo'], '/');
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }

    db_update('hotels', [
        'logo'       => $logo_path,
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => $hotel_id]);

    log_activity('hotel_logo_uploaded', "Logo hôtel \"{$hotel['name']}\" mis à jour");

    json_success([
        'path'     => $logo_path,
        'url'      => (defined('BASE_URL') ? BASE_URL : '') . '/' . $logo_path,
        'filename' => $result['filename'],
        'message'  => "Logo de l'hôtel mis à jour avec succès",
    ]);
}

/**
 * POST action=login_image — Upload the login page background image
 */
function handle_login_image_upload(): void {
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    $dest_dir      = dirname(__DIR__) . '/uploads/logos';

    $result = process_upload('image', $dest_dir, $allowed_mimes, 'login_bg', null);
    if (!$result['success']) {
        json_error($result['error']);
    }

    $image_path = 'uploads/logos/' . $result['filename'];

    // Delete old login image
    $old_path = get_setting('login_image', '');
    if ($old_path) {
        $old_file = dirname(__DIR__) . '/' . ltrim($old_path, '/');
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }

    set_setting('login_image', $image_path);

    log_activity('login_image_uploaded', 'Image de connexion mise à jour : ' . $result['filename']);

    json_success([
        'path'     => $image_path,
        'url'      => (defined('BASE_URL') ? BASE_URL : '') . '/' . $image_path,
        'filename' => $result['filename'],
        'message'  => 'Image de page de connexion mise à jour',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Public proof upload action (no session required)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * POST action=proof&token=X — Upload payment proof (public page)
 * Validates token against booking, then saves the file.
 */
function handle_proof_upload(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
        exit;
    }

    $token = trim($_GET['token'] ?? $_POST['token'] ?? '');

    if (!$token) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token manquant']);
        exit;
    }

    // Validate token
    $booking = db_fetch(
        "SELECT id, reference, payment_status, status FROM bookings WHERE secure_token = ? LIMIT 1",
        [$token]
    );

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Lien de paiement invalide ou expiré']);
        exit;
    }

    if ($booking['status'] === 'cancelled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cette réservation a été annulée']);
        exit;
    }

    if ($booking['payment_status'] === 'validated') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Le paiement de cette réservation est déjà validé']);
        exit;
    }

    if (empty($_FILES['proof'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
        exit;
    }

    // Allow images + PDF for proofs
    $allowed_mimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    $dest_dir = dirname(__DIR__) . '/uploads/proofs';
    $booking_id = (int)$booking['id'];

    // Custom inline process (can't use process_upload — no json_error here)
    $file = $_FILES['proof'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err_labels = [
            UPLOAD_ERR_INI_SIZE  => 'Fichier trop volumineux.',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux.',
            UPLOAD_ERR_PARTIAL   => 'Upload partiel, veuillez réessayer.',
            UPLOAD_ERR_NO_FILE   => 'Aucun fichier sélectionné.',
        ];
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $err_labels[$file['error']] ?? 'Erreur upload.']);
        exit;
    }

    $max_size = 10 * 1048576; // 10 MB for proof uploads
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10 Mo).']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed_mimes, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, WEBP, PDF.']);
        exit;
    }

    $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $ext      = $ext_map[$mime] ?? 'bin';
    $filename = 'proof_' . $booking_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }

    $dest = $dest_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Impossible de sauvegarder le fichier.']);
        exit;
    }

    $file_path = 'uploads/proofs/' . $filename;

    // Insert proof record
    $proof_id = db_insert('payment_proofs', [
        'booking_id'   => $booking_id,
        'file_path'    => $file_path,
        'file_name'    => $filename,
        'mime_type'    => $mime,
        'file_size'    => $file['size'],
        'uploaded_by'  => 'client',
        'admin_id'     => null,
        'status'       => 'pending',
        'uploaded_at'  => date('Y-m-d H:i:s'),
    ]);

    // Update booking payment status
    db_update('bookings', [
        'payment_status' => 'proof_received',
        'updated_at'     => date('Y-m-d H:i:s'),
    ], ['id' => $booking_id]);

    // Create notification for admin
    send_notification('proof_received', [
        'title'      => 'Preuve de paiement reçue',
        'message'    => "Preuve de paiement reçue pour la réservation #{$booking['reference']}",
        'booking_id' => $booking_id,
    ]);

    echo json_encode([
        'success'  => true,
        'proof_id' => $proof_id,
        'message'  => 'Votre justificatif a été envoyé avec succès. Nous vous confirmerons votre paiement dans les plus brefs délais.',
    ]);
    exit;
}
