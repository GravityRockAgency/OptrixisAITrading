<?php
/**
 * Faiza Kids Concierge — Notifications API
 * Manages internal admin notifications
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

// ── Notification factory ───────────────────────────────────────────────────────

/**
 * Create and persist a new notification.
 *
 * @param string   $type       e.g. 'new_booking', 'payment_received', 'proof_received', etc.
 * @param string   $title      Short notification title
 * @param string   $message    Full notification message
 * @param int|null $booking_id Associated booking ID (optional)
 * @return int  Inserted notification ID
 */
function create_notification(string $type, string $title, string $message, ?int $booking_id = null): int {
    return db_insert('notifications', [
        'type'       => $type,
        'title'      => $title,
        'message'    => $message,
        'booking_id' => $booking_id,
        'is_read'    => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

// ── Route dispatcher ──────────────────────────────────────────────────────────
match (true) {
    $method === 'GET'  && $action === 'list'          => action_list(),
    $method === 'GET'  && $action === 'count'         => action_count(),
    $method === 'POST' && $action === 'mark_read'     => action_mark_read(),
    $method === 'POST' && $action === 'mark_all_read' => action_mark_all_read(),
    $method === 'POST' && $action === 'create'        => action_create(),
    $method === 'POST' && $action === 'delete'        => action_delete(),
    default                                           => json_error('Action non reconnue', 404),
};

// ── GET action=list ───────────────────────────────────────────────────────────
function action_list(): void {
    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $unread   = isset($_GET['unread']) ? (bool)(int)$_GET['unread'] : null;
    $type     = $_GET['type'] ?? '';

    $where  = '1=1';
    $params = [];

    if ($unread === true) {
        $where .= ' AND is_read = 0';
    } elseif ($unread === false) {
        $where .= ' AND is_read = 1';
    }

    if ($type) {
        $where    .= ' AND type = ?';
        $params[]  = $type;
    }

    $total = (int) db_query(
        "SELECT COUNT(*) FROM notifications WHERE $where",
        $params
    )->fetchColumn();

    $pager = paginate($total, $per_page, $page);

    $notifications = db_fetch_all(
        "SELECT n.*,
                b.reference  AS booking_reference,
                b.client_name AS booking_client
         FROM notifications n
         LEFT JOIN bookings b ON b.id = n.booking_id
         WHERE $where
         ORDER BY n.created_at DESC
         LIMIT ? OFFSET ?",
        [...$params, $per_page, $pager['offset']]
    );

    // Add relative time
    foreach ($notifications as &$notif) {
        $notif['time_ago'] = time_ago($notif['created_at']);
    }
    unset($notif);

    $unread_count = (int) db_query(
        "SELECT COUNT(*) FROM notifications WHERE is_read = 0"
    )->fetchColumn();

    json_success([
        'notifications' => $notifications,
        'pagination'    => $pager,
        'unread_count'  => $unread_count,
    ]);
}

// ── GET action=count ──────────────────────────────────────────────────────────
function action_count(): void {
    $total_unread = (int) db_query(
        "SELECT COUNT(*) FROM notifications WHERE is_read = 0"
    )->fetchColumn();

    $by_type = db_fetch_all(
        "SELECT type, COUNT(*) AS count
         FROM notifications
         WHERE is_read = 0
         GROUP BY type
         ORDER BY count DESC"
    );

    $type_counts = [];
    foreach ($by_type as $row) {
        $type_counts[$row['type']] = (int)$row['count'];
    }

    // Most recent unread
    $latest = db_fetch(
        "SELECT id, type, title, message, booking_id, created_at
         FROM notifications
         WHERE is_read = 0
         ORDER BY created_at DESC
         LIMIT 1"
    );

    json_success([
        'unread'     => $total_unread,
        'by_type'    => $type_counts,
        'latest'     => $latest,
    ]);
}

// ── POST action=mark_read&id=X ────────────────────────────────────────────────
function action_mark_read(): void {
    global $input;

    $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
    if (!$id) json_error('Identifiant manquant');

    $notification = db_fetch("SELECT id FROM notifications WHERE id = ? LIMIT 1", [$id]);
    if (!$notification) json_error('Notification introuvable', 404);

    db_update('notifications', ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], ['id' => $id]);

    $unread_count = (int) db_query(
        "SELECT COUNT(*) FROM notifications WHERE is_read = 0"
    )->fetchColumn();

    json_success([
        'message'     => 'Notification marquée comme lue',
        'unread_count' => $unread_count,
    ]);
}

// ── POST action=mark_all_read ─────────────────────────────────────────────────
function action_mark_all_read(): void {
    $affected = db_query(
        "UPDATE notifications SET is_read = 1, read_at = ? WHERE is_read = 0",
        [date('Y-m-d H:i:s')]
    )->rowCount();

    json_success([
        'message'  => "$affected notification(s) marquée(s) comme lue(s)",
        'affected' => $affected,
    ]);
}

// ── POST action=create ────────────────────────────────────────────────────────
function action_create(): void {
    global $input;

    $type       = trim($input['type']    ?? 'info');
    $title      = trim($input['title']   ?? '');
    $message    = trim($input['message'] ?? '');
    $booking_id = !empty($input['booking_id']) ? (int)$input['booking_id'] : null;

    if (!$title)   json_error('Le titre est requis');
    if (!$message) json_error('Le message est requis');

    $id = create_notification($type, $title, $message, $booking_id);

    json_success([
        'id'      => $id,
        'message' => 'Notification créée',
    ]);
}

// ── POST action=delete ────────────────────────────────────────────────────────
function action_delete(): void {
    global $input;

    $id    = (int)($_GET['id'] ?? $input['id'] ?? 0);
    $all   = !empty($input['all']) && $input['all'] === true;
    $read  = !empty($input['read_only']);

    if ($all) {
        if ($read) {
            $affected = db_query("DELETE FROM notifications WHERE is_read = 1")->rowCount();
            json_success(['message' => "$affected notification(s) lue(s) supprimée(s)", 'affected' => $affected]);
        }
        $affected = db_query("DELETE FROM notifications")->rowCount();
        json_success(['message' => "Toutes les notifications supprimées ($affected)", 'affected' => $affected]);
    }

    if (!$id) json_error('Identifiant manquant');

    $notification = db_fetch("SELECT id FROM notifications WHERE id = ? LIMIT 1", [$id]);
    if (!$notification) json_error('Notification introuvable', 404);

    db_delete('notifications', 'id', $id);

    json_success(['message' => 'Notification supprimée']);
}
