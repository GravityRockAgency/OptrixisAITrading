<?php
/**
 * Faiza Kids Concierge — Global Search API
 * Searches across bookings, hotels and babysitters simultaneously
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

// ── Only GET is supported ──────────────────────────────────────────────────────
if ($method !== 'GET') {
    json_error('Méthode non autorisée', 405);
}

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    json_error('La recherche doit contenir au moins 2 caractères');
}

$limit = max(1, min(20, (int)($_GET['limit'] ?? 5)));
$like  = '%' . $q . '%';

// ── Search bookings ───────────────────────────────────────────────────────────
$bookings_raw = db_fetch_all(
    "SELECT b.id, b.reference, b.client_name, b.client_whatsapp,
            b.service_date, b.status, b.payment_status,
            h.name AS hotel_name, h.city AS hotel_city
     FROM bookings b
     LEFT JOIN hotels h ON h.id = b.hotel_id
     WHERE b.reference LIKE ?
        OR b.client_name LIKE ?
        OR b.client_whatsapp LIKE ?
     ORDER BY b.service_date DESC
     LIMIT ?",
    [$like, $like, $like, $limit]
);

$bookings = array_map(function (array $b): array {
    $date = $b['service_date'] ? date('d/m/Y', strtotime($b['service_date'])) : '';
    return [
        'type'     => 'booking',
        'id'       => (int)$b['id'],
        'title'    => $b['client_name'] . ' — ' . $b['reference'],
        'subtitle' => trim(
            ($b['hotel_name'] ?? 'Hôtel inconnu')
            . ($b['hotel_city'] ? ', ' . $b['hotel_city'] : '')
            . ($date ? ' · ' . $date : '')
            . ' · ' . get_status_label($b['status'])
        ),
        'meta'     => [
            'reference'      => $b['reference'],
            'status'         => $b['status'],
            'payment_status' => $b['payment_status'],
            'date'           => $b['service_date'],
        ],
        'url'      => '/admin/bookings?id=' . $b['id'],
    ];
}, $bookings_raw);

// ── Search hotels ─────────────────────────────────────────────────────────────
$hotels_raw = db_fetch_all(
    "SELECT id, name, code, city, address, is_active,
            (SELECT COUNT(*) FROM bookings b WHERE b.hotel_id = hotels.id) AS booking_count
     FROM hotels
     WHERE name LIKE ? OR code LIKE ? OR city LIKE ?
     ORDER BY is_active DESC, name ASC
     LIMIT ?",
    [$like, $like, $like, $limit]
);

$hotels = array_map(function (array $h): array {
    return [
        'type'     => 'hotel',
        'id'       => (int)$h['id'],
        'title'    => $h['name'] . ($h['code'] ? ' (' . $h['code'] . ')' : ''),
        'subtitle' => trim(
            ($h['city'] ?? '')
            . ($h['address'] ? ' — ' . truncate($h['address'], 50) : '')
            . ' · ' . $h['booking_count'] . ' réservation(s)'
            . ($h['is_active'] ? '' : ' · Inactif')
        ),
        'meta'     => [
            'code'      => $h['code'],
            'city'      => $h['city'],
            'is_active' => (bool)$h['is_active'],
        ],
        'url'      => '/admin/hotels?id=' . $h['id'],
    ];
}, $hotels_raw);

// ── Search babysitters ────────────────────────────────────────────────────────
$babysitters_raw = db_fetch_all(
    "SELECT bs.id, bs.full_name, bs.phone, bs.status, bs.is_active,
            (SELECT COUNT(*) FROM bookings b WHERE b.babysitter_id = bs.id) AS booking_count,
            (SELECT COUNT(*) FROM babysitter_hotels bh WHERE bh.babysitter_id = bs.id) AS hotel_count
     FROM babysitters bs
     WHERE bs.full_name LIKE ? OR bs.phone LIKE ?
     ORDER BY bs.is_active DESC, bs.full_name ASC
     LIMIT ?",
    [$like, $like, $limit]
);

$status_labels = [
    'available' => 'Disponible',
    'busy'      => 'Occupée',
    'vacation'  => 'En congé',
    'inactive'  => 'Inactive',
];

$babysitters = array_map(function (array $bs) use ($status_labels): array {
    return [
        'type'     => 'babysitter',
        'id'       => (int)$bs['id'],
        'title'    => $bs['full_name'],
        'subtitle' => trim(
            ($bs['phone'] ?? '')
            . ' · ' . ($status_labels[$bs['status']] ?? ucfirst($bs['status']))
            . ' · ' . $bs['booking_count'] . ' réservation(s)'
            . ' · ' . $bs['hotel_count'] . ' hôtel(s)'
            . ($bs['is_active'] ? '' : ' · Inactive')
        ),
        'meta'     => [
            'phone'     => $bs['phone'],
            'status'    => $bs['status'],
            'is_active' => (bool)$bs['is_active'],
        ],
        'url'      => '/admin/babysitters?id=' . $bs['id'],
    ];
}, $babysitters_raw);

// ── Return combined results ────────────────────────────────────────────────────
$total = count($bookings) + count($hotels) + count($babysitters);

json_success([
    'query'       => $q,
    'total'       => $total,
    'bookings'    => $bookings,
    'hotels'      => $hotels,
    'babysitters' => $babysitters,
]);
