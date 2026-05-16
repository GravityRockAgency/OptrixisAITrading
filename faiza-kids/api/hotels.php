<?php
/**
 * Faiza Kids Concierge — Hotels API
 * Handles hotel management operations
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
    $method === 'GET'  && $action === 'list'         => action_list(),
    $method === 'GET'  && $action === 'get'          => action_get(),
    $method === 'POST' && $action === 'create'       => action_create(),
    $method === 'POST' && $action === 'update'       => action_update(),
    $method === 'POST' && $action === 'toggle'       => action_toggle(),
    $method === 'POST' && $action === 'delete'       => action_delete(),
    $method === 'POST' && $action === 'upload_logo'  => action_upload_logo(),
    default                                          => json_error('Action non reconnue', 404),
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function hotel_with_stats(array $hotel): array {
    $hotel_id = (int)$hotel['id'];

    $hotel['booking_count'] = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE hotel_id = ?",
        [$hotel_id]
    )->fetchColumn();

    $hotel['active_booking_count'] = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE hotel_id = ? AND status NOT IN ('cancelled','completed')",
        [$hotel_id]
    )->fetchColumn();

    $month_start = date('Y-m-01');
    $month_end   = date('Y-m-t');

    $hotel['monthly_revenue'] = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings
         WHERE hotel_id = ? AND service_date BETWEEN ? AND ? AND payment_status = 'validated'",
        [$hotel_id, $month_start, $month_end]
    )->fetchColumn();

    $hotel['total_revenue'] = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings
         WHERE hotel_id = ? AND payment_status = 'validated'",
        [$hotel_id]
    )->fetchColumn();

    $hotel['babysitter_count'] = (int) db_query(
        "SELECT COUNT(*) FROM babysitter_hotels WHERE hotel_id = ?",
        [$hotel_id]
    )->fetchColumn();

    return $hotel;
}

function generate_hotel_slug(string $name): string {
    $slug = strtolower($name);
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    $base = $slug;
    $i    = 1;
    while (db_fetch("SELECT id FROM hotels WHERE slug = ? LIMIT 1", [$slug])) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// ── GET action=list ───────────────────────────────────────────────────────────
function action_list(): void {
    $where  = '1=1';
    $params = [];

    if (isset($_GET['active'])) {
        $where    .= ' AND is_active = ?';
        $params[]  = (int)$_GET['active'];
    }
    if (!empty($_GET['search'])) {
        $like      = '%' . $_GET['search'] . '%';
        $where    .= ' AND (name LIKE ? OR city LIKE ? OR code LIKE ?)';
        array_push($params, $like, $like, $like);
    }

    $hotels = db_fetch_all("SELECT * FROM hotels WHERE $where ORDER BY name ASC", $params);

    json_success(['hotels' => $hotels, 'count' => count($hotels)]);
}

// ── GET action=get&id=X ───────────────────────────────────────────────────────
function action_get(): void {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $hotel = db_fetch("SELECT * FROM hotels WHERE id = ? LIMIT 1", [$id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    $hotel = hotel_with_stats($hotel);

    // Fetch assigned babysitters
    $hotel['babysitters'] = db_fetch_all(
        "SELECT bs.id, bs.full_name, bs.phone, bs.status, bs.avatar
         FROM babysitters bs
         INNER JOIN babysitter_hotels bh ON bh.babysitter_id = bs.id
         WHERE bh.hotel_id = ?
         ORDER BY bs.full_name ASC",
        [$id]
    );

    // Recent bookings
    $hotel['recent_bookings'] = db_fetch_all(
        "SELECT b.id, b.reference, b.client_name, b.service_date, b.status, b.final_price
         FROM bookings b
         WHERE b.hotel_id = ?
         ORDER BY b.service_date DESC
         LIMIT 10",
        [$id]
    );

    json_success(['hotel' => $hotel]);
}

// ── POST action=create ────────────────────────────────────────────────────────
function action_create(): void {
    global $input;

    if (empty($input['name'])) json_error('Le nom de l\'hôtel est requis');
    if (empty($input['city'])) json_error('La ville est requise');

    $slug = generate_hotel_slug($input['name']);

    // Generate unique hotel code if not provided
    $code = !empty($input['code']) ? strtoupper(trim($input['code'])) : strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $input['name']), 0, 4));
    $base_code = $code;
    $i = 1;
    while (db_fetch("SELECT id FROM hotels WHERE code = ? LIMIT 1", [$code])) {
        $code = $base_code . $i++;
    }

    $hotel_id = db_insert('hotels', [
        'name'             => trim($input['name']),
        'slug'             => $slug,
        'code'             => $code,
        'city'             => trim($input['city']),
        'address'          => trim($input['address'] ?? ''),
        'phone'            => trim($input['phone'] ?? ''),
        'email'            => trim($input['email'] ?? ''),
        'contact_name'     => trim($input['contact_name'] ?? ''),
        'stars'            => isset($input['stars']) ? (int)$input['stars'] : null,
        'day_rate'         => isset($input['day_rate']) ? (float)$input['day_rate'] : 150,
        'night_rate'       => isset($input['night_rate']) ? (float)$input['night_rate'] : 200,
        'night_start_hour' => $input['night_start_hour'] ?? '20:00:00',
        'night_end_hour'   => $input['night_end_hour'] ?? '08:00:00',
        'commission_pct'   => isset($input['commission_pct']) ? (float)$input['commission_pct'] : 0,
        'notes'            => trim($input['notes'] ?? ''),
        'is_active'        => 1,
        'created_at'       => date('Y-m-d H:i:s'),
        'updated_at'       => date('Y-m-d H:i:s'),
    ]);

    log_activity('hotel_created', "Hôtel \"{$input['name']}\" créé (code: $code)");

    $hotel = db_fetch("SELECT * FROM hotels WHERE id = ? LIMIT 1", [$hotel_id]);

    json_success(['hotel' => $hotel, 'message' => 'Hôtel créé avec succès']);
}

// ── POST action=update&id=X ───────────────────────────────────────────────────
function action_update(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $hotel = db_fetch("SELECT * FROM hotels WHERE id = ? LIMIT 1", [$id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    $allowed = [
        'name', 'city', 'address', 'phone', 'email', 'contact_name',
        'stars', 'day_rate', 'night_rate', 'night_start_hour', 'night_end_hour',
        'commission_pct', 'notes', 'code',
    ];

    $data = ['updated_at' => date('Y-m-d H:i:s')];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $data[$field] = $input[$field];
        }
    }

    // Regenerate slug if name changed
    if (!empty($data['name']) && $data['name'] !== $hotel['name']) {
        $new_slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $data['name']));
        $new_slug = preg_replace('/[^a-z0-9]+/', '-', $new_slug);
        $new_slug = trim($new_slug, '-');
        $base     = $new_slug;
        $i        = 1;
        while ($row = db_fetch("SELECT id FROM hotels WHERE slug = ? LIMIT 1", [$new_slug])) {
            if ((int)$row['id'] === $id) break;
            $new_slug = $base . '-' . $i++;
        }
        $data['slug'] = $new_slug;
    }

    db_update('hotels', $data, ['id' => $id]);

    log_activity('hotel_updated', "Hôtel \"{$hotel['name']}\" mis à jour");

    $updated = db_fetch("SELECT * FROM hotels WHERE id = ? LIMIT 1", [$id]);

    json_success(['hotel' => $updated, 'message' => 'Hôtel mis à jour']);
}

// ── POST action=toggle&id=X ───────────────────────────────────────────────────
function action_toggle(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $hotel = db_fetch("SELECT id, name, is_active FROM hotels WHERE id = ? LIMIT 1", [$id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    $new_state = $hotel['is_active'] ? 0 : 1;

    db_update('hotels', ['is_active' => $new_state, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);

    $label = $new_state ? 'activé' : 'désactivé';
    log_activity('hotel_toggled', "Hôtel \"{$hotel['name']}\" $label");

    json_success([
        'is_active' => $new_state,
        'message'   => "Hôtel $label avec succès",
    ]);
}

// ── POST action=delete&id=X ───────────────────────────────────────────────────
function action_delete(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $hotel = db_fetch("SELECT id, name FROM hotels WHERE id = ? LIMIT 1", [$id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    // Check for active bookings
    $active = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE hotel_id = ? AND status NOT IN ('cancelled','completed')",
        [$id]
    )->fetchColumn();

    if ($active > 0) {
        json_error("Impossible de supprimer cet hôtel : $active réservation(s) active(s) en cours");
    }

    // Remove babysitter associations first
    db_query("DELETE FROM babysitter_hotels WHERE hotel_id = ?", [$id]);

    db_delete('hotels', 'id', $id);

    log_activity('hotel_deleted', "Hôtel \"{$hotel['name']}\" supprimé");

    json_success(['message' => 'Hôtel supprimé avec succès']);
}

// ── POST action=upload_logo&id=X ──────────────────────────────────────────────
function action_upload_logo(): void {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $hotel = db_fetch("SELECT id, name, logo FROM hotels WHERE id = ? LIMIT 1", [$id]);
    if (!$hotel) json_error('Hôtel introuvable', 404);

    if (empty($_FILES['logo'])) {
        json_error('Aucun fichier reçu');
    }

    $allowed_mimes = [
        'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml',
    ];

    $max_size = (int)(get_setting('upload_max_size_mb', '5')) * 1048576;
    $dest_dir = dirname(__DIR__) . '/uploads/logos';

    $result = upload_file($_FILES['logo'], $dest_dir, $allowed_mimes);
    if (!$result['success']) {
        json_error($result['error']);
    }

    // Delete old logo file if exists
    if (!empty($hotel['logo'])) {
        $old = dirname(__DIR__) . '/uploads/logos/' . basename($hotel['logo']);
        if (file_exists($old)) {
            @unlink($old);
        }
    }

    $logo_path = 'uploads/logos/' . $result['filename'];
    db_update('hotels', ['logo' => $logo_path, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);

    log_activity('hotel_logo_uploaded', "Logo mis à jour pour l'hôtel \"{$hotel['name']}\"");

    json_success([
        'logo'    => $logo_path,
        'logo_url' => (defined('BASE_URL') ? BASE_URL : '') . '/' . $logo_path,
        'message' => 'Logo uploadé avec succès',
    ]);
}
