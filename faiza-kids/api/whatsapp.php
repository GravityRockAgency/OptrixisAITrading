<?php
/**
 * Faiza Kids Concierge — WhatsApp API
 * Manages message templates, building messages and logging WhatsApp interactions
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
    $method === 'GET'  && $action === 'templates'     => action_templates(),
    $method === 'GET'  && $action === 'template'      => action_template(),
    $method === 'POST' && $action === 'update_template' => action_update_template(),
    $method === 'GET'  && $action === 'build_message' => action_build_message(),
    $method === 'POST' && $action === 'log'           => action_log(),
    $method === 'GET'  && $action === 'history'       => action_history(),
    $method === 'GET'  && $action === 'history_all'   => action_history_all(),
    default                                           => json_error('Action non reconnue', 404),
};

// ── Core: build a message with variable replacement ───────────────────────────

/**
 * Replace all template variables with real booking data.
 *
 * @param string $template     Raw template string with {placeholders}
 * @param array  $booking      Full booking row (with hotel_name, babysitter_name etc.)
 * @param string $lang         'fr' | 'ar' | 'en'
 * @param array  $extra        Additional variables: ['custom_note' => '...']
 * @return string
 */
function build_message_from_template(string $template, array $booking, string $lang = 'fr', array $extra = []): string {
    // Fetch child names
    $children = db_fetch_all(
        "SELECT name FROM booking_children WHERE booking_id = ? ORDER BY id ASC",
        [(int)$booking['id']]
    );
    $child_names = implode(', ', array_column($children, 'name'));

    // Format date by language
    if ($lang === 'ar') {
        $date_formatted = format_date_ar($booking['service_date']);
    } elseif ($lang === 'en') {
        $ts = strtotime($booking['service_date']);
        $date_formatted = $ts ? date('l, F j, Y', $ts) : $booking['service_date'];
    } else {
        $date_formatted = format_date_fr($booking['service_date']);
    }

    // Format duration
    $duration_str = format_duration((int)($booking['duration_minutes'] ?? 0));

    // Format start time (strip seconds)
    $start_time = substr($booking['start_time'] ?? '', 0, 5);

    // Price display
    $price = (!empty($booking['final_price']))
        ? format_price($booking['final_price'])
        : 'À confirmer';

    // Payment link
    $token        = $booking['secure_token'] ?? '';
    $payment_link = $token
        ? (defined('BASE_URL') ? BASE_URL : '') . '/payment-proof/' . $token
        : '(lien non encore généré)';

    // Bank details from settings
    $bank_name    = get_setting('bank_name', '');
    $account_name = get_setting('bank_account_name', '');
    $rib          = get_setting('bank_rib', '');
    $bank_details = trim("$bank_name\n$account_name\nRIB: $rib");

    $babysitter_name = $booking['babysitter_name'] ?? 'À confirmer';

    $replacements = [
        '{name}'              => $booking['client_name']   ?? '',
        '{booking_reference}' => $booking['reference']      ?? '',
        '{hotel}'             => $booking['hotel_name']     ?? '',
        '{city}'              => $booking['hotel_city']     ?? '',
        '{address}'           => $booking['hotel_address']  ?? '',
        '{date}'              => $date_formatted,
        '{start_time}'        => $start_time,
        '{duration}'          => $duration_str,
        '{children_count}'    => (string)($booking['children_count'] ?? 0),
        '{child_names}'       => $child_names ?: 'Non précisé',
        '{price}'             => $price,
        '{payment_link}'      => $payment_link,
        '{bank_details}'      => $bank_details,
        '{babysitter_name}'   => $babysitter_name,
        '{custom_note}'       => $extra['custom_note'] ?? '',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * Arabic date formatting (basic transliteration).
 */
function format_date_ar(string $date): string {
    if (empty($date)) return '';
    $ts = strtotime($date);
    if (!$ts) return $date;

    $days = [
        'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت',
    ];
    $months = [
        1  => 'يناير',    2  => 'فبراير',   3  => 'مارس',
        4  => 'أبريل',    5  => 'ماي',       6  => 'يونيو',
        7  => 'يوليوز',   8  => 'غشت',       9  => 'شتنبر',
        10 => 'أكتوبر',   11 => 'نونبر',     12 => 'دجنبر',
    ];

    $dow = (int) date('w', $ts);
    $d   = (int) date('j', $ts);
    $m   = (int) date('n', $ts);
    $y   = date('Y', $ts);

    return $days[$dow] . ' ' . $d . ' ' . $months[$m] . ' ' . $y;
}

// ── GET action=templates ──────────────────────────────────────────────────────
function action_templates(): void {
    $templates = db_fetch_all(
        "SELECT * FROM whatsapp_templates ORDER BY template_key ASC, lang ASC"
    );

    // Group by template_key
    $grouped = [];
    foreach ($templates as $tpl) {
        $grouped[$tpl['template_key']][] = $tpl;
    }

    json_success(['templates' => $grouped]);
}

// ── GET action=template&key=X&lang=Y ─────────────────────────────────────────
function action_template(): void {
    $key  = $_GET['key']  ?? '';
    $lang = $_GET['lang'] ?? 'fr';

    if (!$key) json_error('Clé de template manquante');

    $template = db_fetch(
        "SELECT * FROM whatsapp_templates WHERE template_key = ? AND lang = ? LIMIT 1",
        [$key, $lang]
    );

    if (!$template) {
        // Fallback to French
        $template = db_fetch(
            "SELECT * FROM whatsapp_templates WHERE template_key = ? AND lang = 'fr' LIMIT 1",
            [$key]
        );
    }

    if (!$template) json_error('Template introuvable', 404);

    json_success(['template' => $template]);
}

// ── POST action=update_template ───────────────────────────────────────────────
function action_update_template(): void {
    global $input;

    $key     = trim($input['template_key'] ?? '');
    $lang    = trim($input['lang']         ?? 'fr');
    $name    = trim($input['name']         ?? '');
    $message = trim($input['message']      ?? '');

    if (!$key)     json_error('Clé de template manquante');
    if (!$message) json_error('Le message est requis');

    $valid_langs = ['fr', 'ar', 'en'];
    if (!in_array($lang, $valid_langs)) {
        json_error('Langue invalide. Valeurs : ' . implode(', ', $valid_langs));
    }

    $existing = db_fetch(
        "SELECT id FROM whatsapp_templates WHERE template_key = ? AND lang = ? LIMIT 1",
        [$key, $lang]
    );

    if ($existing) {
        $upd = ['message' => $message, 'updated_at' => date('Y-m-d H:i:s')];
        if ($name) $upd['name'] = $name;
        db_update('whatsapp_templates', $upd, ['id' => $existing['id']]);
    } else {
        db_insert('whatsapp_templates', [
            'template_key' => $key,
            'lang'         => $lang,
            'name'         => $name ?: $key,
            'message'      => $message,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    log_activity('whatsapp_template_updated', "Template \"$key\" ($lang) mis à jour");

    $template = db_fetch(
        "SELECT * FROM whatsapp_templates WHERE template_key = ? AND lang = ? LIMIT 1",
        [$key, $lang]
    );

    json_success(['template' => $template, 'message' => 'Template mis à jour avec succès']);
}

// ── GET action=build_message&booking_id=X&template_key=Y&lang=Z ──────────────
function action_build_message(): void {
    $booking_id   = (int)($_GET['booking_id']   ?? 0);
    $template_key = trim($_GET['template_key']  ?? '');
    $lang         = trim($_GET['lang']          ?? 'fr');
    $custom_note  = $_GET['custom_note']         ?? '';

    if (!$booking_id)   json_error('Identifiant de réservation manquant');
    if (!$template_key) json_error('Clé de template manquante');

    // Fetch booking with hotel and babysitter info
    $booking = db_fetch(
        "SELECT b.*,
                h.name    AS hotel_name,
                h.city    AS hotel_city,
                h.address AS hotel_address,
                bs.full_name AS babysitter_name
         FROM bookings b
         LEFT JOIN hotels      h  ON h.id  = b.hotel_id
         LEFT JOIN babysitters bs ON bs.id = b.babysitter_id
         WHERE b.id = ? LIMIT 1",
        [$booking_id]
    );
    if (!$booking) json_error('Réservation introuvable', 404);

    // Fetch template
    $template = db_fetch(
        "SELECT * FROM whatsapp_templates WHERE template_key = ? AND lang = ? LIMIT 1",
        [$template_key, $lang]
    );
    if (!$template) {
        // Try French fallback
        $template = db_fetch(
            "SELECT * FROM whatsapp_templates WHERE template_key = ? AND lang = 'fr' LIMIT 1",
            [$template_key]
        );
    }
    if (!$template) json_error("Template \"$template_key\" introuvable", 404);

    $built_message = build_message_from_template(
        $template['message'],
        $booking,
        $lang,
        ['custom_note' => $custom_note]
    );

    // Normalise WhatsApp number
    $phone        = preg_replace('/[^\d+]/', '', $booking['client_whatsapp']);
    $phone        = ltrim($phone, '+');
    $whatsapp_url = 'https://wa.me/' . $phone . '?text=' . rawurlencode($built_message);

    json_success([
        'message'       => $built_message,
        'whatsapp_url'  => $whatsapp_url,
        'template_key'  => $template_key,
        'lang'          => $lang,
        'client_phone'  => $booking['client_whatsapp'],
    ]);
}

// ── POST action=log ───────────────────────────────────────────────────────────
function action_log(): void {
    global $input;

    $booking_id    = (int)($input['booking_id'] ?? 0);
    $template_key  = trim($input['template_key'] ?? '');
    $lang          = trim($input['lang']         ?? 'fr');
    $event_type    = trim($input['event_type']   ?? 'copied'); // copied | opened | sent
    $message       = trim($input['message']      ?? '');

    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT id, reference FROM bookings WHERE id = ? LIMIT 1", [$booking_id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $valid_events = ['copied', 'opened', 'sent'];
    if (!in_array($event_type, $valid_events)) {
        $event_type = 'copied';
    }

    $admin_id = $_SESSION['admin_id'] ?? null;

    $log_id = db_insert('whatsapp_logs', [
        'booking_id'   => $booking_id,
        'admin_id'     => $admin_id,
        'template_key' => $template_key,
        'lang'         => $lang,
        'event_type'   => $event_type,
        'message'      => $message,
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    json_success([
        'log_id'  => $log_id,
        'message' => 'Action WhatsApp enregistrée',
    ]);
}

// ── GET action=history&booking_id=X ──────────────────────────────────────────
function action_history(): void {
    $booking_id = (int)($_GET['booking_id'] ?? 0);
    if (!$booking_id) json_error('Identifiant de réservation manquant');

    $booking = db_fetch("SELECT id FROM bookings WHERE id = ? LIMIT 1", [$booking_id]);
    if (!$booking) json_error('Réservation introuvable', 404);

    $logs = db_fetch_all(
        "SELECT wl.*, a.username AS admin_username, a.full_name AS admin_name
         FROM whatsapp_logs wl
         LEFT JOIN admins a ON a.id = wl.admin_id
         WHERE wl.booking_id = ?
         ORDER BY wl.created_at DESC",
        [$booking_id]
    );

    json_success(['history' => $logs, 'count' => count($logs)]);
}

// ── GET action=history_all ────────────────────────────────────────────────────
function action_history_all(): void {
    $page     = max(1, (int)($_GET['page']     ?? 1));
    $per_page = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $template = $_GET['template_key'] ?? '';
    $admin_id = (int)($_GET['admin_id'] ?? 0);

    $where  = '1=1';
    $params = [];

    if ($template) {
        $where    .= ' AND wl.template_key = ?';
        $params[]  = $template;
    }
    if ($admin_id) {
        $where    .= ' AND wl.admin_id = ?';
        $params[]  = $admin_id;
    }
    if (!empty($_GET['date_start'])) {
        $where    .= ' AND DATE(wl.created_at) >= ?';
        $params[]  = $_GET['date_start'];
    }
    if (!empty($_GET['date_end'])) {
        $where    .= ' AND DATE(wl.created_at) <= ?';
        $params[]  = $_GET['date_end'];
    }

    $total = (int) db_query("SELECT COUNT(*) FROM whatsapp_logs wl WHERE $where", $params)->fetchColumn();
    $pager = paginate($total, $per_page, $page);

    $logs = db_fetch_all(
        "SELECT wl.*,
                a.username AS admin_username,
                a.full_name AS admin_name,
                b.reference AS booking_reference,
                b.client_name
         FROM whatsapp_logs wl
         LEFT JOIN admins   a ON a.id = wl.admin_id
         LEFT JOIN bookings b ON b.id = wl.booking_id
         WHERE $where
         ORDER BY wl.created_at DESC
         LIMIT ? OFFSET ?",
        [...$params, $per_page, $pager['offset']]
    );

    json_success(['history' => $logs, 'pagination' => $pager]);
}
