<?php
/**
 * Faiza Kids Concierge — Payments API
 * Manages payment requests, proof validation, and payment exports
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

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
    $method === 'GET'  && $action === 'list'         => action_list(),
    $method === 'GET'  && $action === 'stats'        => action_stats(),
    $method === 'POST' && $action === 'request'      => action_request(),
    $method === 'POST' && $action === 'validate'     => action_validate(),
    $method === 'POST' && $action === 'refuse'       => action_refuse(),
    $method === 'GET'  && $action === 'proof'        => action_proof(),
    $method === 'POST' && $action === 'upload_proof' => action_upload_proof(),
    $method === 'GET'  && $action === 'export'       => action_export(),
    default                                          => json_error('Action non reconnue', 404),
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function payment_base_sql(): string {
    return "SELECT b.id, b.reference, b.client_name, b.client_whatsapp, b.client_room,
                   b.service_date, b.start_time, b.duration_minutes, b.children_count,
                   b.status, b.payment_status, b.price_status,
                   b.estimated_price, b.final_price, b.secure_token,
                   b.payment_validated_at, b.created_at, b.updated_at,
                   h.name AS hotel_name, h.city AS hotel_city,
                   bs.full_name AS babysitter_name,
                   pp.id AS proof_id, pp.file_path AS proof_file,
                   pp.uploaded_at AS proof_uploaded_at, pp.status AS proof_status
            FROM bookings b
            LEFT JOIN hotels          h  ON h.id  = b.hotel_id
            LEFT JOIN babysitters     bs ON bs.id = b.babysitter_id
            LEFT JOIN payment_proofs  pp ON pp.booking_id = b.id
                  AND pp.id = (SELECT MAX(id) FROM payment_proofs WHERE booking_id = b.id)";
}

function build_payment_filters(array $get): array {
    $conditions = ['1=1'];
    $params     = [];

    if (!empty($get['payment_status'])) {
        $conditions[] = 'b.payment_status = ?';
        $params[]     = $get['payment_status'];
    }
    if (!empty($get['hotel_id'])) {
        $conditions[] = 'b.hotel_id = ?';
        $params[]     = (int)$get['hotel_id'];
    }
    if (!empty($get['date_start'])) {
        $conditions[] = 'b.service_date >= ?';
        $params[]     = $get['date_start'];
    }
    if (!empty($get['date_end'])) {
        $conditions[] = 'b.service_date <= ?';
        $params[]     = $get['date_end'];
    }
    if (!empty($get['search'])) {
        $like         = '%' . $get['search'] . '%';
        $conditions[] = '(b.reference LIKE ? OR b.client_name LIKE ? OR b.client_whatsapp LIKE ?)';
        array_push($params, $like, $like, $like);
    }

    return [implode(' AND ', $conditions), $params];
}

// ── GET action=list ───────────────────────────────────────────────────────────
function action_list(): void {
    [$where, $params] = build_payment_filters($_GET);

    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = max(1, min(200, (int)($_GET['per_page'] ?? 20)));

    $total = (int) db_query(
        "SELECT COUNT(*) FROM bookings b WHERE $where",
        $params
    )->fetchColumn();

    $pager = paginate($total, $per_page, $page);

    $sql    = payment_base_sql() . " WHERE $where ORDER BY b.updated_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $pager['offset'];

    $payments = db_fetch_all($sql, $params);

    // Append proof URL
    $base_url = defined('BASE_URL') ? BASE_URL : '';
    foreach ($payments as &$p) {
        $p['proof_url'] = $p['proof_file']
            ? $base_url . '/' . $p['proof_file']
            : null;
        $p['payment_link'] = $p['secure_token']
            ? $base_url . '/payment-proof/' . $p['secure_token']
            : null;
    }
    unset($p);

    json_success(['payments' => $payments, 'pagination' => $pager]);
}

// ── GET action=stats ──────────────────────────────────────────────────────────
function action_stats(): void {
    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t');

    $pending_verification = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE payment_status = 'proof_received'"
    )->fetchColumn();

    $pending_request = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE payment_status = 'requested'"
    )->fetchColumn();

    $validated = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE payment_status = 'validated'"
    )->fetchColumn();

    $refused = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE payment_status = 'refused'"
    )->fetchColumn();

    $not_requested = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE payment_status = 'pending' AND status NOT IN ('cancelled')"
    )->fetchColumn();

    $total_validated_amount = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings WHERE payment_status = 'validated'"
    )->fetchColumn();

    $month_validated_amount = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings
         WHERE payment_status = 'validated' AND service_date BETWEEN ? AND ?",
        [$month_start, $month_end]
    )->fetchColumn();

    $month_validated_count = (int) db_query(
        "SELECT COUNT(*) FROM bookings
         WHERE payment_status = 'validated' AND service_date BETWEEN ? AND ?",
        [$month_start, $month_end]
    )->fetchColumn();

    $pending_amount = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings
         WHERE payment_status IN ('requested','proof_received') AND final_price IS NOT NULL"
    )->fetchColumn();

    json_success([
        'stats' => [
            'pending_verification'   => $pending_verification,
            'pending_request'        => $pending_request,
            'not_requested'          => $not_requested,
            'validated'              => $validated,
            'refused'                => $refused,
            'total_validated_amount' => $total_validated_amount,
            'month_validated_amount' => $month_validated_amount,
            'month_validated_count'  => $month_validated_count,
            'pending_amount'         => $pending_amount,
        ],
    ]);
}

// ── POST action=request&booking_id=X ─────────────────────────────────────────
function action_request(): void {
    global $input;

    $booking_id = (int)($input['booking_id'] ?? $_GET['booking_id'] ?? 0);
    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$booking_id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    if ($booking['status'] === 'cancelled') {
        json_error('Impossible de demander un paiement pour une réservation annulée');
    }
    if ($booking['payment_status'] === 'validated') {
        json_error('Le paiement de cette réservation a déjà été validé');
    }

    $data = ['payment_status' => 'requested', 'updated_at' => date('Y-m-d H:i:s')];

    // Generate secure token if not already set
    if (empty($booking['secure_token'])) {
        $data['secure_token'] = generate_secure_token();
    }

    db_update('bookings', $data, ['id' => $booking_id]);

    log_activity('payment_requested', "Paiement demandé pour #{$booking['reference']}", $booking_id);

    $token        = $booking['secure_token'] ?? $data['secure_token'];
    $payment_link = (defined('BASE_URL') ? BASE_URL : '') . '/payment-proof/' . $token;

    // Create notification
    send_notification('payment_requested', [
        'title'      => 'Paiement demandé',
        'message'    => "Paiement demandé pour la réservation #{$booking['reference']}",
        'booking_id' => $booking_id,
    ]);

    json_success([
        'payment_link' => $payment_link,
        'message'      => 'Demande de paiement envoyée',
    ]);
}

// ── POST action=validate&booking_id=X ────────────────────────────────────────
function action_validate(): void {
    global $input;

    $booking_id = (int)($input['booking_id'] ?? $_GET['booking_id'] ?? 0);
    $notes      = trim($input['notes'] ?? '');

    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT id, reference, payment_status FROM bookings WHERE id = ? LIMIT 1", [$booking_id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    if ($booking['payment_status'] === 'validated') {
        json_error('Le paiement est déjà validé');
    }

    db_update('bookings', [
        'payment_status'       => 'validated',
        'payment_validated_at' => date('Y-m-d H:i:s'),
        'updated_at'           => date('Y-m-d H:i:s'),
    ], ['id' => $booking_id]);

    // Update the latest proof record
    db_query(
        "UPDATE payment_proofs
         SET status = 'validated', reviewed_at = ?, review_notes = ?
         WHERE booking_id = ?
         ORDER BY id DESC
         LIMIT 1",
        [date('Y-m-d H:i:s'), $notes, $booking_id]
    );

    log_activity('payment_validated', "Paiement validé pour #{$booking['reference']}", $booking_id);

    send_notification('payment_validated', [
        'title'      => 'Paiement validé',
        'message'    => "Le paiement de la réservation #{$booking['reference']} a été validé",
        'booking_id' => $booking_id,
    ]);

    json_success(['message' => 'Paiement validé avec succès']);
}

// ── POST action=refuse&booking_id=X ──────────────────────────────────────────
function action_refuse(): void {
    global $input;

    $booking_id = (int)($input['booking_id'] ?? $_GET['booking_id'] ?? 0);
    $reason     = trim($input['reason'] ?? '');

    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$booking_id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    db_update('bookings', [
        'payment_status' => 'refused',
        'updated_at'     => date('Y-m-d H:i:s'),
    ], ['id' => $booking_id]);

    db_query(
        "UPDATE payment_proofs
         SET status = 'refused', refuse_reason = ?, reviewed_at = ?
         WHERE booking_id = ?
         ORDER BY id DESC
         LIMIT 1",
        [$reason, date('Y-m-d H:i:s'), $booking_id]
    );

    log_activity(
        'payment_refused',
        "Paiement refusé pour #{$booking['reference']}. Raison : $reason",
        $booking_id
    );

    send_notification('payment_refused', [
        'title'      => 'Paiement refusé',
        'message'    => "Le paiement de la réservation #{$booking['reference']} a été refusé",
        'booking_id' => $booking_id,
    ]);

    json_success(['message' => 'Paiement refusé']);
}

// ── GET action=proof&booking_id=X ─────────────────────────────────────────────
function action_proof(): void {
    $booking_id = (int)($_GET['booking_id'] ?? 0);
    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch(
        "SELECT id, reference, payment_status, secure_token, final_price FROM bookings WHERE id = ? LIMIT 1",
        [$booking_id]
    );
    if (!$booking) json_error('Réservation introuvable', 404);

    $proofs = db_fetch_all(
        "SELECT * FROM payment_proofs WHERE booking_id = ? ORDER BY id DESC",
        [$booking_id]
    );

    $base_url = defined('BASE_URL') ? BASE_URL : '';
    foreach ($proofs as &$proof) {
        $proof['file_url'] = $proof['file_path']
            ? $base_url . '/' . $proof['file_path']
            : null;
    }
    unset($proof);

    $payment_link = $booking['secure_token']
        ? $base_url . '/payment-proof/' . $booking['secure_token']
        : null;

    json_success([
        'booking'      => $booking,
        'proofs'       => $proofs,
        'payment_link' => $payment_link,
    ]);
}

// ── POST action=upload_proof&booking_id=X ─────────────────────────────────────
function action_upload_proof(): void {
    $booking_id = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$booking_id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    if (empty($_FILES['proof'])) {
        json_error('Aucun fichier reçu');
    }

    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/webp',
        'application/pdf',
    ];

    $dest_dir = dirname(__DIR__) . '/uploads/proofs';
    $result   = upload_file($_FILES['proof'], $dest_dir, $allowed_mimes);

    if (!$result['success']) {
        json_error($result['error']);
    }

    $file_path = 'uploads/proofs/' . $result['filename'];

    $proof_id = db_insert('payment_proofs', [
        'booking_id'    => $booking_id,
        'file_path'     => $file_path,
        'file_name'     => $result['filename'],
        'uploaded_by'   => 'admin',
        'admin_id'      => $_SESSION['admin_id'] ?? null,
        'status'        => 'pending',
        'uploaded_at'   => date('Y-m-d H:i:s'),
    ]);

    // Update booking payment status to proof_received
    db_update('bookings', [
        'payment_status' => 'proof_received',
        'updated_at'     => date('Y-m-d H:i:s'),
    ], ['id' => $booking_id]);

    log_activity('proof_uploaded_admin', "Preuve de paiement uploadée pour #{$booking['reference']}", $booking_id);

    $base_url = defined('BASE_URL') ? BASE_URL : '';

    json_success([
        'proof_id'   => $proof_id,
        'file_path'  => $file_path,
        'file_url'   => $base_url . '/' . $file_path,
        'message'    => 'Preuve de paiement uploadée avec succès',
    ]);
}

// ── GET action=export ─────────────────────────────────────────────────────────
function action_export(): void {
    [$where, $params] = build_payment_filters($_GET);

    $payments = db_fetch_all(
        payment_base_sql() . " WHERE $where ORDER BY b.service_date DESC, b.updated_at DESC",
        $params
    );

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="paiements_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM

    fputcsv($out, [
        'Référence', 'Client', 'WhatsApp', 'Hôtel', 'Date prestation',
        'Prix estimé', 'Prix final', 'Statut paiement',
        'Preuve reçue', 'Validé le', 'Statut preuve',
    ], ';');

    foreach ($payments as $p) {
        fputcsv($out, [
            $p['reference'],
            $p['client_name'],
            $p['client_whatsapp'],
            $p['hotel_name'] ?? '',
            $p['service_date'],
            $p['estimated_price'] ?? '',
            $p['final_price'] ?? '',
            $p['payment_status'],
            $p['proof_uploaded_at'] ?? '',
            $p['payment_validated_at'] ?? '',
            $p['proof_status'] ?? '',
        ], ';');
    }

    fclose($out);
    exit;
}
