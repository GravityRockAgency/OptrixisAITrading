<?php
/**
 * Faiza Kids Concierge — Babysitters API
 * Handles babysitter management operations
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
    $method === 'GET'  && $action === 'list'          => action_list(),
    $method === 'GET'  && $action === 'get'           => action_get(),
    $method === 'GET'  && $action === 'available'     => action_available(),
    $method === 'POST' && $action === 'create'        => action_create(),
    $method === 'POST' && $action === 'update'        => action_update(),
    $method === 'POST' && $action === 'toggle'        => action_toggle(),
    $method === 'POST' && $action === 'status'        => action_status(),
    $method === 'POST' && $action === 'assign_hotel'  => action_assign_hotel(),
    $method === 'POST' && $action === 'remove_hotel'  => action_remove_hotel(),
    $method === 'POST' && $action === 'delete'        => action_delete(),
    default                                           => json_error('Action non reconnue', 404),
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function get_babysitter_hotels(int $babysitter_id): array {
    return db_fetch_all(
        "SELECT h.id, h.name, h.city, h.code, h.is_active
         FROM hotels h
         INNER JOIN babysitter_hotels bh ON bh.hotel_id = h.id
         WHERE bh.babysitter_id = ?
         ORDER BY h.name ASC",
        [$babysitter_id]
    );
}

function get_babysitter_stats(int $babysitter_id): array {
    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t');

    $total = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE babysitter_id = ?",
        [$babysitter_id]
    )->fetchColumn();

    $month = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE babysitter_id = ? AND service_date BETWEEN ? AND ?",
        [$babysitter_id, $month_start, $month_end]
    )->fetchColumn();

    $completed = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE babysitter_id = ? AND status = 'completed'",
        [$babysitter_id]
    )->fetchColumn();

    $upcoming = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE babysitter_id = ? AND service_date >= ? AND status IN ('confirmed','pending','new')",
        [$babysitter_id, date('Y-m-d')]
    )->fetchColumn();

    return [
        'total_bookings'    => $total,
        'month_bookings'    => $month,
        'completed_bookings'=> $completed,
        'upcoming_bookings' => $upcoming,
    ];
}

// ── GET action=list ───────────────────────────────────────────────────────────
function action_list(): void {
    $where  = '1=1';
    $params = [];

    if (!empty($_GET['status'])) {
        $where    .= ' AND bs.status = ?';
        $params[]  = $_GET['status'];
    }
    if (isset($_GET['active'])) {
        $where    .= ' AND bs.is_active = ?';
        $params[]  = (int)$_GET['active'];
    }
    if (!empty($_GET['hotel_id'])) {
        $where    .= ' AND EXISTS (SELECT 1 FROM babysitter_hotels bh WHERE bh.babysitter_id = bs.id AND bh.hotel_id = ?)';
        $params[]  = (int)$_GET['hotel_id'];
    }
    if (!empty($_GET['search'])) {
        $like      = '%' . $_GET['search'] . '%';
        $where    .= ' AND (bs.full_name LIKE ? OR bs.phone LIKE ?)';
        array_push($params, $like, $like);
    }

    $babysitters = db_fetch_all(
        "SELECT bs.*,
                (SELECT COUNT(*) FROM bookings b WHERE b.babysitter_id = bs.id) AS total_bookings,
                (SELECT COUNT(*) FROM babysitter_hotels bh WHERE bh.babysitter_id = bs.id) AS hotel_count
         FROM babysitters bs
         WHERE $where
         ORDER BY bs.full_name ASC",
        $params
    );

    json_success(['babysitters' => $babysitters, 'count' => count($babysitters)]);
}

// ── GET action=get&id=X ───────────────────────────────────────────────────────
function action_get(): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $babysitter = db_fetch("SELECT * FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    $babysitter['hotels']       = get_babysitter_hotels($id);
    $babysitter['stats']        = get_babysitter_stats($id);

    // Upcoming bookings
    $babysitter['upcoming_bookings'] = db_fetch_all(
        "SELECT b.id, b.reference, b.client_name, b.service_date, b.start_time,
                b.end_time, b.status, h.name AS hotel_name
         FROM bookings b
         LEFT JOIN hotels h ON h.id = b.hotel_id
         WHERE b.babysitter_id = ? AND b.service_date >= ?
         ORDER BY b.service_date ASC, b.start_time ASC
         LIMIT 20",
        [$id, date('Y-m-d')]
    );

    // Availability: bookings next 7 days
    $babysitter['week_schedule'] = db_fetch_all(
        "SELECT service_date, start_time, end_time, status, reference
         FROM bookings
         WHERE babysitter_id = ?
           AND service_date BETWEEN ? AND ?
           AND status NOT IN ('cancelled')
         ORDER BY service_date ASC, start_time ASC",
        [$id, date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))]
    );

    json_success(['babysitter' => $babysitter]);
}

// ── GET action=available&date=YYYY-MM-DD&time=HH:MM ──────────────────────────
function action_available(): void {
    $date     = $_GET['date']     ?? date('Y-m-d');
    $time     = $_GET['time']     ?? '';
    $duration = (int)($_GET['duration'] ?? 120);
    $hotel_id = (int)($_GET['hotel_id'] ?? 0);

    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        json_error('Date invalide (format attendu : YYYY-MM-DD)');
    }

    // End time of the requested slot
    if ($time) {
        $slot_start = strtotime($date . ' ' . $time);
        $slot_end   = $slot_start + $duration * 60;
        $end_time   = date('H:i:s', $slot_end);
    } else {
        $slot_start = null;
        $end_time   = null;
    }

    // Build base query: active babysitters
    $where  = 'bs.is_active = 1';
    $params = [];

    if ($hotel_id) {
        $where   .= ' AND EXISTS (SELECT 1 FROM babysitter_hotels bh WHERE bh.babysitter_id = bs.id AND bh.hotel_id = ?)';
        $params[] = $hotel_id;
    }

    $all_sitters = db_fetch_all(
        "SELECT bs.id, bs.full_name, bs.phone, bs.status, bs.avatar, bs.languages, bs.certifications
         FROM babysitters bs
         WHERE $where
         ORDER BY bs.full_name ASC",
        $params
    );

    $available = [];
    $busy      = [];

    foreach ($all_sitters as $sitter) {
        // Check if they have a conflicting booking on this date/time
        if ($slot_start && $end_time) {
            $conflict = db_fetch(
                "SELECT id FROM bookings
                 WHERE babysitter_id = ?
                   AND service_date = ?
                   AND status NOT IN ('cancelled')
                   AND start_time < ?
                   AND end_time   > ?
                 LIMIT 1",
                [$sitter['id'], $date, $end_time, $time]
            );
        } else {
            // No time specified — check just the date
            $conflict = db_fetch(
                "SELECT id FROM bookings
                 WHERE babysitter_id = ?
                   AND service_date = ?
                   AND status NOT IN ('cancelled')
                 LIMIT 1",
                [$sitter['id'], $date]
            );
        }

        $sitter['is_available'] = !$conflict;

        if ($conflict) {
            $busy[]      = $sitter;
        } else {
            $available[] = $sitter;
        }
    }

    json_success([
        'available' => $available,
        'busy'      => $busy,
        'date'      => $date,
        'time'      => $time,
        'duration'  => $duration,
    ]);
}

// ── POST action=create ────────────────────────────────────────────────────────
function action_create(): void {
    global $input;

    if (empty($input['full_name'])) json_error('Le nom complet est requis');
    if (empty($input['phone']))     json_error('Le téléphone est requis');

    $babysitter_id = db_insert('babysitters', [
        'full_name'      => trim($input['full_name']),
        'phone'          => trim($input['phone']),
        'email'          => trim($input['email'] ?? ''),
        'whatsapp'       => trim($input['whatsapp'] ?? $input['phone'] ?? ''),
        'date_of_birth'  => $input['date_of_birth'] ?? null,
        'id_number'      => trim($input['id_number'] ?? ''),
        'languages'      => trim($input['languages'] ?? 'Arabe, Français'),
        'certifications' => trim($input['certifications'] ?? ''),
        'experience_years'=> isset($input['experience_years']) ? (int)$input['experience_years'] : 0,
        'bio'            => trim($input['bio'] ?? ''),
        'status'         => $input['status'] ?? 'available',
        'is_active'      => 1,
        'notes'          => trim($input['notes'] ?? ''),
        'created_at'     => date('Y-m-d H:i:s'),
        'updated_at'     => date('Y-m-d H:i:s'),
    ]);

    // Assign to hotels if provided
    if (!empty($input['hotel_ids']) && is_array($input['hotel_ids'])) {
        foreach ($input['hotel_ids'] as $hotel_id) {
            $hotel_id = (int)$hotel_id;
            if (!$hotel_id) continue;
            $exists = db_fetch(
                "SELECT id FROM babysitter_hotels WHERE babysitter_id = ? AND hotel_id = ? LIMIT 1",
                [$babysitter_id, $hotel_id]
            );
            if (!$exists) {
                db_insert('babysitter_hotels', [
                    'babysitter_id' => $babysitter_id,
                    'hotel_id'      => $hotel_id,
                    'assigned_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    log_activity('babysitter_created', "Babysitter \"{$input['full_name']}\" créée");

    $babysitter           = db_fetch("SELECT * FROM babysitters WHERE id = ? LIMIT 1", [$babysitter_id]);
    $babysitter['hotels'] = get_babysitter_hotels($babysitter_id);

    json_success(['babysitter' => $babysitter, 'message' => 'Babysitter créée avec succès']);
}

// ── POST action=update&id=X ───────────────────────────────────────────────────
function action_update(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $babysitter = db_fetch("SELECT id, full_name FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    $allowed = [
        'full_name', 'phone', 'email', 'whatsapp', 'date_of_birth',
        'id_number', 'languages', 'certifications', 'experience_years',
        'bio', 'notes',
    ];

    $data = ['updated_at' => date('Y-m-d H:i:s')];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $data[$field] = $input[$field] !== '' ? $input[$field] : null;
        }
    }

    db_update('babysitters', $data, ['id' => $id]);

    log_activity('babysitter_updated', "Babysitter \"{$babysitter['full_name']}\" mise à jour");

    $updated           = db_fetch("SELECT * FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    $updated['hotels'] = get_babysitter_hotels($id);

    json_success(['babysitter' => $updated, 'message' => 'Babysitter mise à jour']);
}

// ── POST action=toggle&id=X ───────────────────────────────────────────────────
function action_toggle(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $babysitter = db_fetch("SELECT id, full_name, is_active FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    $new_state = $babysitter['is_active'] ? 0 : 1;

    db_update('babysitters', [
        'is_active'  => $new_state,
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => $id]);

    $label = $new_state ? 'activée' : 'désactivée';
    log_activity('babysitter_toggled', "Babysitter \"{$babysitter['full_name']}\" $label");

    json_success(['is_active' => $new_state, 'message' => "Babysitter $label"]);
}

// ── POST action=status&id=X ───────────────────────────────────────────────────
function action_status(): void {
    global $input;

    $id     = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $status = trim($input['status'] ?? '');
    $valid  = ['available', 'busy', 'vacation', 'inactive'];

    if (!$id)                        json_error('Identifiant manquant');
    if (!in_array($status, $valid))  json_error('Statut invalide. Valeurs : ' . implode(', ', $valid));

    $babysitter = db_fetch("SELECT id, full_name FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    db_update('babysitters', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);

    log_activity('babysitter_status', "Statut babysitter \"{$babysitter['full_name']}\" → $status");

    json_success(['status' => $status, 'message' => 'Statut mis à jour']);
}

// ── POST action=assign_hotel&id=X ─────────────────────────────────────────────
function action_assign_hotel(): void {
    global $input;

    $id       = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $hotel_id = (int)($input['hotel_id'] ?? 0);

    if (!$id)       json_error('Identifiant babysitter manquant');
    if (!$hotel_id) json_error('Identifiant hôtel manquant');

    $babysitter = db_fetch("SELECT id, full_name FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    $hotel = db_fetch("SELECT id, name FROM hotels WHERE id = ? LIMIT 1", [$hotel_id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    $exists = db_fetch(
        "SELECT id FROM babysitter_hotels WHERE babysitter_id = ? AND hotel_id = ? LIMIT 1",
        [$id, $hotel_id]
    );

    if ($exists) {
        json_error('Cette babysitter est déjà assignée à cet hôtel');
    }

    db_insert('babysitter_hotels', [
        'babysitter_id' => $id,
        'hotel_id'      => $hotel_id,
        'assigned_at'   => date('Y-m-d H:i:s'),
    ]);

    log_activity('babysitter_hotel_assigned',
        "Babysitter \"{$babysitter['full_name']}\" assignée à l'hôtel \"{$hotel['name']}\""
    );

    json_success([
        'hotels'  => get_babysitter_hotels($id),
        'message' => "Babysitter assignée à l'hôtel \"{$hotel['name']}\"",
    ]);
}

// ── POST action=remove_hotel&id=X ─────────────────────────────────────────────
function action_remove_hotel(): void {
    global $input;

    $id       = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $hotel_id = (int)($input['hotel_id'] ?? 0);

    if (!$id)       json_error('Identifiant babysitter manquant');
    if (!$hotel_id) json_error('Identifiant hôtel manquant');

    $babysitter = db_fetch("SELECT id, full_name FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    $hotel = db_fetch("SELECT id, name FROM hotels WHERE id = ? LIMIT 1", [$hotel_id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    db_query(
        "DELETE FROM babysitter_hotels WHERE babysitter_id = ? AND hotel_id = ?",
        [$id, $hotel_id]
    );

    log_activity('babysitter_hotel_removed',
        "Babysitter \"{$babysitter['full_name']}\" retirée de l'hôtel \"{$hotel['name']}\""
    );

    json_success([
        'hotels'  => get_babysitter_hotels($id),
        'message' => "Babysitter retirée de l'hôtel \"{$hotel['name']}\"",
    ]);
}

// ── POST action=delete&id=X ───────────────────────────────────────────────────
function action_delete(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $babysitter = db_fetch("SELECT id, full_name FROM babysitters WHERE id = ? LIMIT 1", [$id]);
    if (!$babysitter) json_error('Babysitter introuvable', 404);

    // Check active/upcoming bookings
    $active = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE babysitter_id = ? AND status IN ('new','pending','confirmed','in_progress')",
        [$id]
    )->fetchColumn();

    if ($active > 0) {
        json_error("Impossible de supprimer : $active réservation(s) active(s) assignée(s) à cette babysitter");
    }

    // Remove hotel assignments
    db_query("DELETE FROM babysitter_hotels WHERE babysitter_id = ?", [$id]);

    // Unassign from past bookings (set to null)
    db_query("UPDATE bookings SET babysitter_id = NULL WHERE babysitter_id = ?", [$id]);

    db_delete('babysitters', 'id', $id);

    log_activity('babysitter_deleted', "Babysitter \"{$babysitter['full_name']}\" supprimée");

    json_success(['message' => 'Babysitter supprimée avec succès']);
}
