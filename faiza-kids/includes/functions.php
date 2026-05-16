<?php
/**
 * Faiza Kids Concierge - Utility Functions
 */

require_once __DIR__ . '/db.php';

// ── Reference & Token Generation ──────────────────────────────────────────────

/**
 * Generate a unique booking reference like FK-2024-XXXXX
 *
 * @return string
 */
function generate_reference(): string {
    $year = date('Y');
    do {
        $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $ref    = "FK-{$year}-{$suffix}";
        $exists = db_fetch("SELECT id FROM bookings WHERE reference = ? LIMIT 1", [$ref]);
    } while ($exists);
    return $ref;
}

/**
 * Generate a cryptographically secure random token
 *
 * @param int $length  Byte length (hex output will be double)
 * @return string
 */
function generate_secure_token(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

// ── Date & Time Formatting ─────────────────────────────────────────────────────

/**
 * Format a date string in French long format
 * e.g. "lundi 12 juin 2024"
 *
 * @param string $date  Any strtotime-compatible date string or Y-m-d
 * @return string
 */
function format_date_fr(string $date): string {
    if (empty($date)) return '';
    $ts = is_numeric($date) ? (int)$date : strtotime($date);
    if ($ts === false) return $date;

    $days   = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
    $months = [
        1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',
        7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre',
    ];

    $dow   = (int) date('w', $ts);
    $d     = (int) date('j', $ts);
    $m     = (int) date('n', $ts);
    $y     = date('Y', $ts);

    return $days[$dow] . ' ' . $d . ' ' . $months[$m] . ' ' . $y;
}

/**
 * Format a duration in minutes to a human string like "2h30"
 *
 * @param int $minutes
 * @return string
 */
function format_duration(int $minutes): string {
    if ($minutes <= 0) return '0min';
    $h   = intdiv($minutes, 60);
    $min = $minutes % 60;
    if ($h > 0 && $min > 0) {
        return "{$h}h" . str_pad((string)$min, 2, '0', STR_PAD_LEFT);
    } elseif ($h > 0) {
        return "{$h}h";
    } else {
        return "{$min}min";
    }
}

/**
 * Return "il y a X" French relative time string
 *
 * @param string $datetime  MySQL datetime string or strtotime-compatible
 * @return string
 */
function time_ago(string $datetime): string {
    $ts  = strtotime($datetime);
    $now = time();
    $diff = $now - $ts;

    if ($diff < 60) {
        return 'il y a quelques secondes';
    } elseif ($diff < 3600) {
        $m = (int) round($diff / 60);
        return "il y a {$m} min";
    } elseif ($diff < 86400) {
        $h = (int) round($diff / 3600);
        return "il y a {$h}h";
    } elseif ($diff < 2592000) {
        $d = (int) round($diff / 86400);
        return "il y a {$d} jour" . ($d > 1 ? 's' : '');
    } elseif ($diff < 31536000) {
        $mo = (int) round($diff / 2592000);
        return "il y a {$mo} mois";
    } else {
        $y = (int) round($diff / 31536000);
        return "il y a {$y} an" . ($y > 1 ? 's' : '');
    }
}

// ── Price & String Formatting ──────────────────────────────────────────────────

/**
 * Format a price amount as "150 DH"
 *
 * @param float|int|string $amount
 * @param string           $currency
 * @return string
 */
function format_price($amount, string $currency = 'DH'): string {
    if ($amount === null || $amount === '') return '— DH';
    return number_format((float)$amount, 0, ',', ' ') . ' ' . $currency;
}

/**
 * Sanitize a string for HTML output
 *
 * @param string $str
 * @return string
 */
function sanitize(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Truncate a string to a given length, appending ellipsis
 *
 * @param string $str
 * @param int    $length
 * @param string $append
 * @return string
 */
function truncate(string $str, int $length = 80, string $append = '…'): string {
    $str = trim($str);
    if (mb_strlen($str) <= $length) return $str;
    return mb_substr($str, 0, $length) . $append;
}

// ── Status Helpers ─────────────────────────────────────────────────────────────

/**
 * Return French label for a booking status
 *
 * @param string $status
 * @return string
 */
function get_status_label(string $status): string {
    $labels = [
        'new'         => 'Nouvelle',
        'pending'     => 'En attente',
        'confirmed'   => 'Confirmée',
        'in_progress' => 'En cours',
        'completed'   => 'Terminée',
        'cancelled'   => 'Annulée',
        'no_show'     => 'No Show',
    ];
    return $labels[$status] ?? ucfirst($status);
}

/**
 * Return CSS badge class for a booking status
 *
 * @param string $status
 * @return string
 */
function get_status_badge_class(string $status): string {
    $classes = [
        'new'         => 'badge-new',
        'pending'     => 'badge-pending',
        'confirmed'   => 'badge-confirmed',
        'in_progress' => 'badge-inprogress',
        'completed'   => 'badge-completed',
        'cancelled'   => 'badge-cancelled',
        'no_show'     => 'badge-noshow',
    ];
    return $classes[$status] ?? 'badge-default';
}

// ── Settings ───────────────────────────────────────────────────────────────────

/**
 * Retrieve a setting value from the settings table
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $row = db_fetch("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
    $cache[$key] = $row ? (string)$row['setting_value'] : $default;
    return $cache[$key];
}

/**
 * Save (insert or update) a setting value
 *
 * @param string $key
 * @param string $value
 */
function set_setting(string $key, string $value): void {
    $existing = db_fetch("SELECT id FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
    if ($existing) {
        db_update('settings', ['setting_value' => $value], ['setting_key' => $key]);
    } else {
        db_insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
    }
    // Bust cache
    // (static cache in get_setting won't be invalidated in this request but that's acceptable)
}

// ── Notifications ──────────────────────────────────────────────────────────────

/**
 * Log/send an internal notification
 *
 * @param string     $type
 * @param array      $data   Keys: title, message, booking_id (optional)
 * @return int  Inserted notification ID
 */
function send_notification(string $type, array $data): int {
    return db_insert('notifications', [
        'type'       => $type,
        'title'      => $data['title']      ?? '',
        'message'    => $data['message']    ?? '',
        'booking_id' => $data['booking_id'] ?? null,
        'is_read'    => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

// ── WhatsApp ───────────────────────────────────────────────────────────────────

/**
 * Generate a wa.me deep-link
 *
 * @param string $phone    Phone number (international, no + or spaces needed; function normalises)
 * @param string $message  Plain text message to pre-fill
 * @return string
 */
function get_whatsapp_link(string $phone, string $message = ''): string {
    // Normalise: keep digits and leading +
    $phone = preg_replace('/[^\d+]/', '', $phone);
    $phone = ltrim($phone, '+');
    $url   = 'https://wa.me/' . $phone;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

// ── File Upload ────────────────────────────────────────────────────────────────

/**
 * Handle a secure file upload
 *
 * @param array  $file           $_FILES['field'] element
 * @param string $dest_dir       Destination directory (absolute path)
 * @param array  $allowed_types  Allowed MIME types
 * @return array  ['success'=>bool, 'path'=>string, 'error'=>string]
 */
function upload_file(array $file, string $dest_dir, array $allowed_types = []): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err_map = [
            UPLOAD_ERR_INI_SIZE   => 'Le fichier dépasse la taille maximum autorisée.',
            UPLOAD_ERR_FORM_SIZE  => 'Le fichier dépasse la taille du formulaire.',
            UPLOAD_ERR_PARTIAL    => 'Upload partiel.',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier envoyé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire introuvable.',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier.',
            UPLOAD_ERR_EXTENSION  => 'Upload bloqué par extension.',
        ];
        return ['success' => false, 'path' => '', 'error' => $err_map[$file['error']] ?? 'Erreur inconnue.'];
    }

    $max_size = defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : 5242880;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'path' => '', 'error' => 'Fichier trop volumineux (max ' . round($max_size / 1048576, 1) . ' Mo).'];
    }

    // Verify MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!empty($allowed_types) && !in_array($mime, $allowed_types, true)) {
        return ['success' => false, 'path' => '', 'error' => 'Type de fichier non autorisé (' . $mime . ').'];
    }

    // Create destination directory if needed
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }

    // Generate a safe unique filename
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = rtrim($dest_dir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'path' => '', 'error' => 'Impossible de déplacer le fichier uploadé.'];
    }

    return ['success' => true, 'path' => $dest, 'filename' => $filename, 'error' => ''];
}

// ── Activity Logging ───────────────────────────────────────────────────────────

/**
 * Write an activity log entry
 *
 * @param string   $action
 * @param string   $details
 * @param int|null $booking_id
 */
function log_activity(string $action, string $details = '', ?int $booking_id = null): void {
    $admin_id = $_SESSION['admin_id'] ?? null;
    $ip       = $_SERVER['REMOTE_ADDR'] ?? null;
    db_insert('activity_logs', [
        'admin_id'   => $admin_id,
        'action'     => $action,
        'details'    => $details,
        'booking_id' => $booking_id,
        'ip_address' => $ip,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

// ── Hotel Helpers ──────────────────────────────────────────────────────────────

/**
 * Fetch a hotel row by its slug
 *
 * @param string $slug
 * @return array|null
 */
function get_hotel_by_slug(string $slug): ?array {
    return db_fetch("SELECT * FROM hotels WHERE slug = ? AND is_active = 1 LIMIT 1", [$slug]);
}

// ── Price Calculation ──────────────────────────────────────────────────────────

/**
 * Calculate an estimated booking price
 *
 * @param int    $hotel_id
 * @param string $date            Y-m-d
 * @param string $start_time      H:i or H:i:s
 * @param int    $duration_mins
 * @param int    $children_count
 * @return float
 */
function calculate_booking_price(int $hotel_id, string $date, string $start_time, int $duration_mins, int $children_count = 1): float {
    $hotel = db_fetch("SELECT * FROM hotels WHERE id = ? LIMIT 1", [$hotel_id]);
    if (!$hotel) return 0.0;

    $day_rate    = (float)($hotel['day_rate']    ?? 150);
    $night_rate  = (float)($hotel['night_rate']  ?? 200);
    $night_start = strtotime($date . ' ' . ($hotel['night_start_hour'] ?? '20:00:00'));
    $night_end   = strtotime($date . ' ' . ($hotel['night_end_hour']   ?? '08:00:00'));

    // If night_end is before night_start (crosses midnight), add one day
    if ($night_end < $night_start) {
        $night_end = strtotime('+1 day', $night_end);
    }

    $session_start = strtotime($date . ' ' . $start_time);
    $session_end   = $session_start + ($duration_mins * 60);

    // Calculate overlap with night window
    $night_overlap = 0;
    if ($session_end > $night_start && $session_start < $night_end) {
        $overlap_start  = max($session_start, $night_start);
        $overlap_end    = min($session_end,   $night_end);
        $night_overlap  = max(0, ($overlap_end - $overlap_start) / 60); // minutes
    }

    $day_minutes   = $duration_mins - $night_overlap;
    $day_hours     = $day_minutes   / 60;
    $night_hours   = $night_overlap / 60;

    $base_price = ($day_hours * $day_rate) + ($night_hours * $night_rate);

    // Multiplier for multiple children (10% per extra child)
    if ($children_count > 1) {
        $base_price += $base_price * ($children_count - 1) * 0.10;
    }

    return round($base_price, 2);
}

// ── Pagination ─────────────────────────────────────────────────────────────────

/**
 * Build a pagination data structure
 *
 * @param int $total        Total number of records
 * @param int $per_page
 * @param int $current_page
 * @return array  ['total','per_page','current','last','offset','has_prev','has_next','pages']
 */
function paginate(int $total, int $per_page = 20, int $current_page = 1): array {
    $per_page     = max(1, $per_page);
    $current_page = max(1, $current_page);
    $last_page    = (int) ceil($total / $per_page);
    $last_page    = max(1, $last_page);
    $current_page = min($current_page, $last_page);
    $offset       = ($current_page - 1) * $per_page;

    // Build page list with ellipses
    $pages = [];
    $range = 2; // pages around current
    for ($i = 1; $i <= $last_page; $i++) {
        if ($i === 1 || $i === $last_page
            || ($i >= $current_page - $range && $i <= $current_page + $range)
        ) {
            $pages[] = $i;
        } elseif (end($pages) !== '...') {
            $pages[] = '...';
        }
    }

    return [
        'total'    => $total,
        'per_page' => $per_page,
        'current'  => $current_page,
        'last'     => $last_page,
        'offset'   => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $last_page,
        'pages'    => $pages,
    ];
}
