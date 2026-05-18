<?php
/**
 * Faiza Kids Concierge — Bookings API
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

match (true) {
    $method === 'GET'  && $action === 'list'              => action_list(),
    $method === 'GET'  && $action === 'get'               => action_get(),
    $method === 'GET'  && $action === 'today'             => action_today(),
    $method === 'GET'  && $action === 'calendar'          => action_calendar(),
    $method === 'GET'  && $action === 'stats'             => action_stats(),
    $method === 'GET'  && $action === 'export'            => action_export(),
    $method === 'POST' && $action === 'create'            => action_create(),
    $method === 'POST' && $action === 'update'            => action_update(),
    $method === 'POST' && $action === 'status'            => action_status(),
    $method === 'POST' && $action === 'confirm'           => action_confirm(),
    $method === 'POST' && $action === 'cancel'            => action_cancel(),
    $method === 'POST' && $action === 'complete'          => action_complete(),
    $method === 'POST' && $action === 'assign_babysitter' => action_assign_babysitter(),
    $method === 'POST' && $action === 'set_price'         => action_set_price(),
    $method === 'POST' && $action === 'request_payment'   => action_request_payment(),
    $method === 'POST' && $action === 'validate_payment'  => action_validate_payment(),
    $method === 'POST' && $action === 'refuse_payment'    => action_refuse_payment(),
    default                                               => json_error('Action non reconnue', 404),
};

function booking_base_sql(): string {
    return "SELECT b.*,
                   h.name        AS hotel_name,
                   h.city        AS hotel_city,
                   h.address     AS hotel_address,
                   h.code        AS hotel_code,
                   bs.full_name  AS babysitter_name,
                   bs.phone      AS babysitter_phone
            FROM bookings b
            LEFT JOIN hotels      h  ON h.id  = b.hotel_id
            LEFT JOIN babysitters bs ON bs.id = b.babysitter_id";
}

function get_booking_children(int $booking_id): array {
    return db_fetch_all(
        "SELECT * FROM booking_children WHERE booking_id = ? ORDER BY sort_order, id ASC",
        [$booking_id]
    );
}

function build_list_filters(array $get): array {
    $conditions = ['1=1'];
    $params     = [];

    if (!empty($get['type'])) {
        $conditions[] = 'b.type = ?';
        $params[]     = $get['type'];
    }
    if (!empty($get['status'])) {
        $conditions[] = 'b.status = ?';
        $params[]     = $get['status'];
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

function action_list(): void {
    [$where, $params] = build_list_filters($_GET);

    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = max(1, min(200, (int)($_GET['per_page'] ?? 20)));

    $count_sql = "SELECT COUNT(*) FROM bookings b LEFT JOIN hotels h ON h.id = b.hotel_id WHERE $where";
    $total     = (int) db_query($count_sql, $params)->fetchColumn();

    $pager    = paginate($total, $per_page, $page);
    $sql      = booking_base_sql() . " WHERE $where ORDER BY b.service_date DESC, b.start_time DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $pager['offset'];

    $bookings = db_fetch_all($sql, $params);

    json_success(['bookings' => $bookings, 'pagination' => $pager]);
}

function action_get(): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch(booking_base_sql() . " WHERE b.id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $booking['children']         = get_booking_children($id);
    $booking['whatsapp_history'] = db_fetch_all(
        "SELECT * FROM whatsapp_history WHERE booking_id = ? ORDER BY sent_at DESC",
        [$id]
    );
    $booking['payment_proof'] = db_fetch(
        "SELECT * FROM payment_proofs WHERE booking_id = ? ORDER BY id DESC LIMIT 1",
        [$id]
    );

    json_success(['booking' => $booking]);
}

function action_today(): void {
    $today    = date('Y-m-d');
    $bookings = db_fetch_all(
        booking_base_sql() . " WHERE b.service_date = ? ORDER BY b.start_time ASC",
        [$today]
    );
    json_success(['bookings' => $bookings, 'date' => $today]);
}

function action_calendar(): void {
    $week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
    $ts         = strtotime($week_start);
    if (!$ts) json_error('Date de début invalide');

    $week_end = date('Y-m-d', strtotime('+6 days', $ts));

    $bookings = db_fetch_all(
        booking_base_sql() . " WHERE b.service_date BETWEEN ? AND ? ORDER BY b.service_date ASC, b.start_time ASC",
        [$week_start, $week_end]
    );

    $calendar = [];
    foreach ($bookings as $b) {
        $calendar[$b['service_date']][] = $b;
    }

    json_success(['calendar' => $calendar, 'week_start' => $week_start, 'week_end' => $week_end]);
}

function action_create(): void {
    global $input;

    $required = ['client_name', 'client_whatsapp', 'service_date', 'start_time', 'duration_minutes', 'children_count'];
    foreach ($required as $field) {
        if (empty($input[$field]) && $input[$field] !== '0' && $input[$field] !== 0) {
            json_error("Champ requis manquant : $field");
        }
    }

    $duration = (int)$input['duration_minutes'];
    if ($duration <= 0) json_error('La durée doit être supérieure à zéro');

    $children_count = (int)$input['children_count'];
    if ($children_count <= 0) json_error('Le nombre d\'enfants doit être supérieur à zéro');

    $start_ts = strtotime($input['service_date'] . ' ' . $input['start_time']);
    if (!$start_ts) json_error('Date ou heure de début invalide');
    $end_time = date('H:i:s', $start_ts + $duration * 60);

    $hotel_id = !empty($input['hotel_id']) ? (int)$input['hotel_id'] : null;

    $suggested_price = null;
    if ($hotel_id) {
        $suggested_price = calculate_booking_price(
            $hotel_id,
            $input['service_date'],
            $input['start_time'],
            $duration,
            $children_count
        );
    }

    $reference = generate_reference();

    db_begin();
    try {
        $booking_id = db_insert('bookings', [
            'reference'        => $reference,
            'hotel_id'         => $hotel_id,
            'client_name'      => trim($input['client_name']),
            'client_whatsapp'  => trim($input['client_whatsapp']),
            'client_email'     => trim($input['client_email'] ?? ''),
            'room_number'      => trim($input['room_number'] ?? ''),
            'service_date'     => $input['service_date'],
            'start_time'       => $input['start_time'],
            'end_time'         => $end_time,
            'duration_minutes' => $duration,
            'children_count'   => $children_count,
            'type'             => $input['type'] ?? 'hotel',
            'status'           => 'new',
            'price_status'     => 'pending',
            'suggested_price'  => $suggested_price,
            'final_price'      => null,
            'payment_status'   => 'not_required',
            'babysitter_id'    => !empty($input['babysitter_id']) ? (int)$input['babysitter_id'] : null,
            'source'           => $input['source'] ?? 'admin',
            'internal_notes'   => trim($input['internal_notes'] ?? ''),
            'secure_token'     => generate_secure_token(),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        if (!empty($input['children']) && is_array($input['children'])) {
            foreach ($input['children'] as $child) {
                if (empty($child['child_name'])) continue;
                db_insert('booking_children', [
                    'booking_id' => $booking_id,
                    'child_name' => trim($child['child_name']),
                    'age'        => isset($child['age']) ? (int)$child['age'] : null,
                    'notes'      => trim($child['notes'] ?? ''),
                ]);
            }
        }

        db_commit();
    } catch (Exception $e) {
        db_rollback();
        error_log('bookings.php create error: ' . $e->getMessage());
        json_error('Erreur lors de la création de la réservation');
    }

    log_activity('booking_created', "Réservation $reference créée", $booking_id);

    $booking             = db_fetch(booking_base_sql() . " WHERE b.id = ? LIMIT 1", [$booking_id]);
    $booking['children'] = get_booking_children($booking_id);

    json_success(['booking' => $booking, 'message' => 'Réservation créée avec succès']);
}

function action_update(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $allowed = [
        'client_name', 'client_whatsapp', 'client_email', 'room_number',
        'hotel_id', 'service_date', 'start_time', 'duration_minutes',
        'children_count', 'type', 'status', 'babysitter_id',
        'internal_notes', 'source', 'suggested_price', 'final_price',
        'price_status', 'payment_status',
    ];

    $data = ['updated_at' => date('Y-m-d H:i:s')];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $data[$field] = $input[$field] !== '' ? $input[$field] : null;
        }
    }

    $service_date     = $data['service_date']     ?? $booking['service_date'];
    $start_time       = $data['start_time']       ?? $booking['start_time'];
    $duration_minutes = $data['duration_minutes'] ?? $booking['duration_minutes'];
    $start_ts = strtotime($service_date . ' ' . $start_time);
    if ($start_ts) {
        $data['end_time'] = date('H:i:s', $start_ts + (int)$duration_minutes * 60);
    }

    db_update('bookings', $data, ['id' => $id]);

    if (isset($input['children']) && is_array($input['children'])) {
        db_query("DELETE FROM booking_children WHERE booking_id = ?", [$id]);
        foreach ($input['children'] as $child) {
            if (empty($child['child_name'])) continue;
            db_insert('booking_children', [
                'booking_id' => $id,
                'child_name' => trim($child['child_name']),
                'age'        => isset($child['age']) ? (int)$child['age'] : null,
                'notes'      => trim($child['notes'] ?? ''),
            ]);
        }
    }

    log_activity('booking_updated', "Réservation #{$booking['reference']} mise à jour", $id);

    $updated             = db_fetch(booking_base_sql() . " WHERE b.id = ? LIMIT 1", [$id]);
    $updated['children'] = get_booking_children($id);

    json_success(['booking' => $updated, 'message' => 'Réservation mise à jour']);
}

function action_status(): void {
    global $input;

    $id     = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $status = trim($input['status'] ?? '');
    $valid  = ['new', 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];

    if (!$id)                       json_error('Identifiant manquant');
    if (!in_array($status, $valid)) json_error('Statut invalide');

    $booking = db_fetch("SELECT id, reference, status FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    db_update('bookings', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);

    log_activity('booking_status', "Statut → $status pour #{$booking['reference']}", $id);

    json_success(['status' => $status, 'message' => 'Statut mis à jour']);
}

function action_confirm(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    if ($booking['status'] === 'cancelled') {
        json_error('Impossible de confirmer une réservation annulée');
    }

    db_update('bookings', [
        'status'       => 'confirmed',
        'confirmed_at' => date('Y-m-d H:i:s'),
        'updated_at'   => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    log_activity('booking_confirmed', "Réservation #{$booking['reference']} confirmée", $id);

    json_success(['message' => 'Réservation confirmée']);
}

function action_cancel(): void {
    global $input;

    $id     = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $reason = trim($input['reason'] ?? '');

    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    if ($booking['status'] === 'completed') json_error('Impossible d\'annuler une réservation terminée');
    if ($booking['status'] === 'cancelled')  json_error('Cette réservation est déjà annulée');

    $service_ts  = strtotime($booking['service_date'] . ' ' . $booking['start_time']);
    $hours_until = ($service_ts - time()) / 3600;
    $late_cancel = $hours_until < 24 && $hours_until > 0;

    $notes = $booking['internal_notes'];
    if ($reason) $notes .= ($notes ? "\n" : '') . 'Annulation : ' . $reason;

    db_update('bookings', [
        'status'              => 'cancelled',
        'cancelled_at'        => date('Y-m-d H:i:s'),
        'cancellation_reason' => $reason,
        'internal_notes'      => $notes,
        'updated_at'          => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    log_activity('booking_cancelled', "Réservation #{$booking['reference']} annulée. Raison: $reason", $id);

    json_success([
        'message'     => 'Réservation annulée',
        'late_cancel' => $late_cancel,
        'warning'     => $late_cancel ? 'Annulation tardive (moins de 24h avant la prestation)' : null,
    ]);
}

function action_complete(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    if ($booking['status'] === 'cancelled') json_error('Impossible de terminer une réservation annulée');

    db_update('bookings', [
        'status'       => 'completed',
        'completed_at' => date('Y-m-d H:i:s'),
        'updated_at'   => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    log_activity('booking_completed', "Réservation #{$booking['reference']} marquée terminée", $id);

    json_success(['message' => 'Réservation marquée comme terminée']);
}

function action_assign_babysitter(): void {
    global $input;

    $id            = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $babysitter_id = (int)($input['babysitter_id'] ?? 0);

    if (!$id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $sitter_name = null;
    if ($babysitter_id) {
        $sitter = db_fetch("SELECT id, full_name FROM babysitters WHERE id = ? LIMIT 1", [$babysitter_id]);
        if (!$sitter) json_error('Babysitter introuvable', 404);
        $sitter_name = $sitter['full_name'];
    }

    db_update('bookings', [
        'babysitter_id' => $babysitter_id ?: null,
        'updated_at'    => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    $detail = $sitter_name
        ? "Babysitter $sitter_name assignée à #{$booking['reference']}"
        : "Babysitter désassignée de #{$booking['reference']}";

    log_activity('babysitter_assigned', $detail, $id);

    json_success(['message' => 'Babysitter assignée', 'babysitter_name' => $sitter_name]);
}

function action_set_price(): void {
    global $input;

    $id    = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $price = $input['final_price'] ?? null;

    if (!$id)                              json_error('Identifiant manquant');
    if ($price === null || $price === '')   json_error('Prix manquant');
    if ((float)$price < 0)                 json_error('Le prix ne peut pas être négatif');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $price_status = $input['price_status'] ?? 'confirmed';

    db_update('bookings', [
        'final_price'  => (float)$price,
        'price_status' => $price_status,
        'updated_at'   => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    log_activity('price_set', "Prix fixé à " . format_price($price) . " pour #{$booking['reference']}", $id);

    json_success(['message' => 'Prix enregistré', 'final_price' => (float)$price]);
}

function action_request_payment(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT * FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $data = ['payment_status' => 'requested', 'updated_at' => date('Y-m-d H:i:s')];

    if (empty($booking['secure_token'])) {
        $data['secure_token'] = generate_secure_token();
    }

    db_update('bookings', $data, ['id' => $id]);

    log_activity('payment_requested', "Paiement demandé pour #{$booking['reference']}", $id);

    $token        = $booking['secure_token'] ?? $data['secure_token'];
    $payment_link = (defined('BASE_URL') ? BASE_URL : '') . '/payment-proof/' . $token;

    json_success(['message' => 'Paiement demandé', 'payment_link' => $payment_link]);
}

function action_validate_payment(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    db_update('bookings', [
        'payment_status' => 'validated',
        'updated_at'     => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    db_query(
        "UPDATE payment_proofs SET status = 'approved', verified_at = ? WHERE booking_id = ? ORDER BY id DESC LIMIT 1",
        [date('Y-m-d H:i:s'), $id]
    );

    log_activity('payment_validated', "Paiement validé pour #{$booking['reference']}", $id);

    json_success(['message' => 'Paiement validé']);
}

function action_refuse_payment(): void {
    global $input;

    $id     = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $reason = trim($input['reason'] ?? '');

    if (!$id) json_error('Identifiant manquant');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    db_update('bookings', [
        'payment_status' => 'refused',
        'updated_at'     => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    db_query(
        "UPDATE payment_proofs SET status = 'rejected', notes = ?, verified_at = ? WHERE booking_id = ? ORDER BY id DESC LIMIT 1",
        [$reason, date('Y-m-d H:i:s'), $id]
    );

    log_activity('payment_refused', "Paiement refusé pour #{$booking['reference']}. Raison: $reason", $id);

    json_success(['message' => 'Paiement refusé']);
}

function action_stats(): void {
    $today       = date('Y-m-d');
    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t');

    $total_bookings  = db_count('bookings');
    $today_bookings  = (int) db_query("SELECT COUNT(*) FROM bookings WHERE service_date = ?", [$today])->fetchColumn();
    $month_bookings  = (int) db_query("SELECT COUNT(*) FROM bookings WHERE service_date BETWEEN ? AND ?", [$month_start, $month_end])->fetchColumn();
    $pending_count   = (int) db_query("SELECT COUNT(*) FROM bookings WHERE status IN ('new','pending')")->fetchColumn();
    $confirmed_count = (int) db_query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $completed_count = (int) db_query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
    $cancelled_count = (int) db_query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();

    $month_revenue = (float) db_query(
        "SELECT COALESCE(SUM(final_price),0) FROM bookings WHERE service_date BETWEEN ? AND ? AND payment_status = 'validated'",
        [$month_start, $month_end]
    )->fetchColumn();

    $total_revenue = (float) db_query(
        "SELECT COALESCE(SUM(final_price),0) FROM bookings WHERE payment_status = 'validated'"
    )->fetchColumn();

    $pending_payment = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE payment_status IN ('requested','proof_sent')"
    )->fetchColumn();

    json_success([
        'stats' => [
            'total_bookings'  => $total_bookings,
            'today_bookings'  => $today_bookings,
            'month_bookings'  => $month_bookings,
            'pending'         => $pending_count,
            'confirmed'       => $confirmed_count,
            'completed'       => $completed_count,
            'cancelled'       => $cancelled_count,
            'month_revenue'   => $month_revenue,
            'total_revenue'   => $total_revenue,
            'pending_payment' => $pending_payment,
        ],
    ]);
}

function action_export(): void {
    [$where, $params] = build_list_filters($_GET);

    $bookings = db_fetch_all(
        booking_base_sql() . " WHERE $where ORDER BY b.service_date DESC, b.start_time DESC",
        $params
    );

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="reservations_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Référence', 'Date', 'Heure début', 'Heure fin', 'Durée (min)',
        'Client', 'WhatsApp', 'Hôtel', 'Ville', 'Chambre',
        'Enfants', 'Babysitter', 'Statut', 'Prix suggéré', 'Prix final',
        'Statut paiement', 'Source', 'Notes', 'Créé le',
    ], ';');

    foreach ($bookings as $b) {
        fputcsv($out, [
            $b['reference'],
            $b['service_date'],
            $b['start_time'],
            $b['end_time'],
            $b['duration_minutes'],
            $b['client_name'],
            $b['client_whatsapp'],
            $b['hotel_name'] ?? '',
            $b['hotel_city'] ?? '',
            $b['room_number'] ?? '',
            $b['children_count'],
            $b['babysitter_name'] ?? '',
            get_status_label($b['status']),
            $b['suggested_price'] ?? '',
            $b['final_price'] ?? '',
            $b['payment_status'],
            $b['source'] ?? '',
            $b['internal_notes'] ?? '',
            $b['created_at'],
        ], ';');
    }

    fclose($out);
    exit;
}
