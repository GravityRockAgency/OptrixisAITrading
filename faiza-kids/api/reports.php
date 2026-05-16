<?php
/**
 * Faiza Kids Concierge — Reports API
 * Statistics, analytics and exports for the admin dashboard
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
    $method === 'GET' && $action === 'stats'                => action_stats(),
    $method === 'GET' && $action === 'revenue_by_hotel'     => action_revenue_by_hotel(),
    $method === 'GET' && $action === 'booking_sources'      => action_booking_sources(),
    $method === 'GET' && $action === 'hourly_slots'         => action_hourly_slots(),
    $method === 'GET' && $action === 'monthly_trend'        => action_monthly_trend(),
    $method === 'GET' && $action === 'babysitter_performance' => action_babysitter_performance(),
    $method === 'GET' && $action === 'export_csv'           => action_export_csv(),
    $method === 'GET' && $action === 'export_pdf'           => action_export_pdf(),
    default                                                 => json_error('Action non reconnue', 404),
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function get_date_range(): array {
    $date_start = $_GET['date_start'] ?? date('Y-m-01');
    $date_end   = $_GET['date_end']   ?? date('Y-m-t');

    // Basic validation
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_start)) {
        $date_start = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_end)) {
        $date_end = date('Y-m-t');
    }
    if ($date_end < $date_start) {
        $date_end = $date_start;
    }

    return [$date_start, $date_end];
}

// ── GET action=stats ──────────────────────────────────────────────────────────
function action_stats(): void {
    [$date_start, $date_end] = get_date_range();

    $total = (int) db_query(
        "SELECT COUNT(*) FROM bookings WHERE service_date BETWEEN ? AND ?",
        [$date_start, $date_end]
    )->fetchColumn();

    $by_status = db_fetch_all(
        "SELECT status, COUNT(*) AS count
         FROM bookings
         WHERE service_date BETWEEN ? AND ?
         GROUP BY status",
        [$date_start, $date_end]
    );

    $status_map = [];
    foreach ($by_status as $row) {
        $status_map[$row['status']] = (int)$row['count'];
    }

    $revenue = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings
         WHERE service_date BETWEEN ? AND ? AND payment_status = 'validated'",
        [$date_start, $date_end]
    )->fetchColumn();

    $revenue_pending = (float) db_query(
        "SELECT COALESCE(SUM(final_price), 0) FROM bookings
         WHERE service_date BETWEEN ? AND ?
           AND payment_status IN ('requested','proof_received')
           AND final_price IS NOT NULL",
        [$date_start, $date_end]
    )->fetchColumn();

    $avg_price = (float) db_query(
        "SELECT COALESCE(AVG(final_price), 0) FROM bookings
         WHERE service_date BETWEEN ? AND ? AND final_price IS NOT NULL",
        [$date_start, $date_end]
    )->fetchColumn();

    $avg_duration = (float) db_query(
        "SELECT COALESCE(AVG(duration_minutes), 0) FROM bookings
         WHERE service_date BETWEEN ? AND ?",
        [$date_start, $date_end]
    )->fetchColumn();

    $avg_children = (float) db_query(
        "SELECT COALESCE(AVG(children_count), 0) FROM bookings
         WHERE service_date BETWEEN ? AND ?",
        [$date_start, $date_end]
    )->fetchColumn();

    $total_hours = (float) db_query(
        "SELECT COALESCE(SUM(duration_minutes), 0) / 60 FROM bookings
         WHERE service_date BETWEEN ? AND ? AND status = 'completed'",
        [$date_start, $date_end]
    )->fetchColumn();

    $hotel_count = (int) db_query(
        "SELECT COUNT(DISTINCT hotel_id) FROM bookings WHERE service_date BETWEEN ? AND ?",
        [$date_start, $date_end]
    )->fetchColumn();

    json_success([
        'stats' => [
            'date_start'       => $date_start,
            'date_end'         => $date_end,
            'total_bookings'   => $total,
            'by_status'        => $status_map,
            'revenue'          => round($revenue, 2),
            'revenue_pending'  => round($revenue_pending, 2),
            'avg_price'        => round($avg_price, 2),
            'avg_duration_min' => round($avg_duration, 1),
            'avg_children'     => round($avg_children, 2),
            'total_hours'      => round($total_hours, 1),
            'active_hotels'    => $hotel_count,
        ],
    ]);
}

// ── GET action=revenue_by_hotel ───────────────────────────────────────────────
function action_revenue_by_hotel(): void {
    [$date_start, $date_end] = get_date_range();

    $rows = db_fetch_all(
        "SELECT h.id, h.name AS hotel_name, h.city, h.code,
                COUNT(b.id) AS booking_count,
                COALESCE(SUM(CASE WHEN b.payment_status = 'validated' THEN b.final_price ELSE 0 END), 0) AS validated_revenue,
                COALESCE(SUM(b.estimated_price), 0) AS estimated_revenue,
                COALESCE(SUM(b.final_price), 0) AS total_final_price,
                COALESCE(AVG(b.final_price), 0) AS avg_price,
                COUNT(CASE WHEN b.status = 'completed' THEN 1 END) AS completed_count,
                COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) AS cancelled_count
         FROM bookings b
         LEFT JOIN hotels h ON h.id = b.hotel_id
         WHERE b.service_date BETWEEN ? AND ?
         GROUP BY b.hotel_id, h.id, h.name, h.city, h.code
         ORDER BY validated_revenue DESC",
        [$date_start, $date_end]
    );

    json_success(['revenue_by_hotel' => $rows, 'date_start' => $date_start, 'date_end' => $date_end]);
}

// ── GET action=booking_sources ────────────────────────────────────────────────
function action_booking_sources(): void {
    [$date_start, $date_end] = get_date_range();

    $rows = db_fetch_all(
        "SELECT source,
                COUNT(*) AS count,
                COALESCE(SUM(final_price), 0) AS revenue
         FROM bookings
         WHERE service_date BETWEEN ? AND ?
         GROUP BY source
         ORDER BY count DESC",
        [$date_start, $date_end]
    );

    $total = array_sum(array_column($rows, 'count'));
    foreach ($rows as &$row) {
        $row['percentage'] = $total > 0 ? round($row['count'] / $total * 100, 1) : 0;
    }
    unset($row);

    json_success(['sources' => $rows, 'total' => $total, 'date_start' => $date_start, 'date_end' => $date_end]);
}

// ── GET action=hourly_slots ───────────────────────────────────────────────────
function action_hourly_slots(): void {
    [$date_start, $date_end] = get_date_range();

    $rows = db_fetch_all(
        "SELECT HOUR(start_time) AS hour,
                COUNT(*) AS count,
                COALESCE(AVG(duration_minutes), 0) AS avg_duration
         FROM bookings
         WHERE service_date BETWEEN ? AND ?
           AND status NOT IN ('cancelled')
         GROUP BY HOUR(start_time)
         ORDER BY hour ASC",
        [$date_start, $date_end]
    );

    // Fill in all 24 hours
    $all_hours = array_fill(0, 24, ['count' => 0, 'avg_duration' => 0]);
    foreach ($rows as $row) {
        $h = (int)$row['hour'];
        $all_hours[$h] = [
            'hour'         => $h,
            'label'        => sprintf('%02d:00', $h),
            'count'        => (int)$row['count'],
            'avg_duration' => round((float)$row['avg_duration'], 1),
        ];
    }

    // Add hour and label to unfilled slots
    foreach ($all_hours as $h => &$slot) {
        if (!isset($slot['hour'])) {
            $slot['hour']  = $h;
            $slot['label'] = sprintf('%02d:00', $h);
        }
    }
    unset($slot);

    json_success([
        'hourly_slots' => array_values($all_hours),
        'date_start'   => $date_start,
        'date_end'     => $date_end,
    ]);
}

// ── GET action=monthly_trend&year=YYYY ───────────────────────────────────────
function action_monthly_trend(): void {
    $year = (int)($_GET['year'] ?? date('Y'));
    if ($year < 2020 || $year > 2099) {
        json_error('Année invalide');
    }

    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $start = sprintf('%04d-%02d-01', $year, $m);
        $end   = date('Y-m-t', strtotime($start));

        $row = db_fetch(
            "SELECT COUNT(*) AS booking_count,
                    COALESCE(SUM(CASE WHEN payment_status = 'validated' THEN final_price ELSE 0 END), 0) AS revenue,
                    COALESCE(SUM(final_price), 0) AS total_price,
                    COUNT(CASE WHEN status = 'completed'  THEN 1 END) AS completed,
                    COUNT(CASE WHEN status = 'cancelled'  THEN 1 END) AS cancelled
             FROM bookings
             WHERE service_date BETWEEN ? AND ?",
            [$start, $end]
        );

        $months[] = [
            'month'         => $m,
            'month_label'   => format_month_fr($m),
            'year'          => $year,
            'date_start'    => $start,
            'date_end'      => $end,
            'booking_count' => (int)$row['booking_count'],
            'revenue'       => (float)$row['revenue'],
            'total_price'   => (float)$row['total_price'],
            'completed'     => (int)$row['completed'],
            'cancelled'     => (int)$row['cancelled'],
        ];
    }

    json_success(['months' => $months, 'year' => $year]);
}

function format_month_fr(int $month): string {
    $names = [
        1  => 'Janvier',   2  => 'Février',  3  => 'Mars',
        4  => 'Avril',     5  => 'Mai',       6  => 'Juin',
        7  => 'Juillet',   8  => 'Août',      9  => 'Septembre',
        10 => 'Octobre',   11 => 'Novembre',  12 => 'Décembre',
    ];
    return $names[$month] ?? "Mois $month";
}

// ── GET action=babysitter_performance ─────────────────────────────────────────
function action_babysitter_performance(): void {
    [$date_start, $date_end] = get_date_range();

    $rows = db_fetch_all(
        "SELECT bs.id, bs.full_name, bs.status AS babysitter_status,
                COUNT(b.id) AS booking_count,
                COUNT(CASE WHEN b.status = 'completed' THEN 1 END) AS completed,
                COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) AS cancelled,
                COALESCE(SUM(b.duration_minutes), 0) AS total_minutes,
                COALESCE(SUM(CASE WHEN b.payment_status = 'validated' THEN b.final_price ELSE 0 END), 0) AS revenue
         FROM babysitters bs
         LEFT JOIN bookings b ON b.babysitter_id = bs.id
              AND b.service_date BETWEEN ? AND ?
         GROUP BY bs.id, bs.full_name, bs.status
         ORDER BY completed DESC, booking_count DESC",
        [$date_start, $date_end]
    );

    foreach ($rows as &$row) {
        $row['total_hours']       = round((float)$row['total_minutes'] / 60, 1);
        $row['completion_rate']   = $row['booking_count'] > 0
            ? round($row['completed'] / $row['booking_count'] * 100, 1)
            : 0;
    }
    unset($row);

    json_success([
        'babysitters' => $rows,
        'date_start'  => $date_start,
        'date_end'    => $date_end,
    ]);
}

// ── GET action=export_csv ─────────────────────────────────────────────────────
function action_export_csv(): void {
    [$date_start, $date_end] = get_date_range();

    $bookings = db_fetch_all(
        "SELECT b.*,
                h.name       AS hotel_name,
                h.city       AS hotel_city,
                h.code       AS hotel_code,
                bs.full_name AS babysitter_name
         FROM bookings b
         LEFT JOIN hotels      h  ON h.id  = b.hotel_id
         LEFT JOIN babysitters bs ON bs.id = b.babysitter_id
         WHERE b.service_date BETWEEN ? AND ?
         ORDER BY b.service_date ASC, b.start_time ASC",
        [$date_start, $date_end]
    );

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rapport_' . $date_start . '_' . $date_end . '.csv"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Référence', 'Date', 'Heure début', 'Heure fin', 'Durée (min)',
        'Client', 'WhatsApp', 'Email', 'Chambre',
        'Hôtel', 'Ville', 'Code hôtel',
        'Enfants', 'Babysitter',
        'Type', 'Statut', 'Source',
        'Prix estimé (DH)', 'Prix final (DH)', 'Statut prix', 'Statut paiement',
        'Notes', 'Créé le',
    ], ';');

    foreach ($bookings as $b) {
        fputcsv($out, [
            $b['reference'],
            $b['service_date'],
            substr($b['start_time'], 0, 5),
            substr($b['end_time'] ?? '', 0, 5),
            $b['duration_minutes'],
            $b['client_name'],
            $b['client_whatsapp'],
            $b['client_email'] ?? '',
            $b['client_room'] ?? '',
            $b['hotel_name'] ?? '',
            $b['hotel_city'] ?? '',
            $b['hotel_code'] ?? '',
            $b['children_count'],
            $b['babysitter_name'] ?? '',
            $b['booking_type'] ?? '',
            get_status_label($b['status']),
            $b['source'] ?? '',
            $b['estimated_price'] ?? '',
            $b['final_price'] ?? '',
            $b['price_status'] ?? '',
            $b['payment_status'],
            $b['notes'] ?? '',
            $b['created_at'],
        ], ';');
    }

    fclose($out);
    exit;
}

// ── GET action=export_pdf ─────────────────────────────────────────────────────
function action_export_pdf(): void {
    [$date_start, $date_end] = get_date_range();

    // Compute summary stats
    $stats = db_fetch(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN payment_status = 'validated' THEN final_price ELSE 0 END), 0) AS revenue,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) AS confirmed,
                COALESCE(AVG(duration_minutes), 0) AS avg_duration,
                COALESCE(AVG(final_price), 0) AS avg_price
         FROM bookings
         WHERE service_date BETWEEN ? AND ?",
        [$date_start, $date_end]
    );

    $by_hotel = db_fetch_all(
        "SELECT h.name AS hotel_name, h.city,
                COUNT(b.id) AS count,
                COALESCE(SUM(CASE WHEN b.payment_status='validated' THEN b.final_price ELSE 0 END),0) AS revenue
         FROM bookings b
         LEFT JOIN hotels h ON h.id = b.hotel_id
         WHERE b.service_date BETWEEN ? AND ?
         GROUP BY b.hotel_id, h.name, h.city
         ORDER BY revenue DESC",
        [$date_start, $date_end]
    );

    $by_babysitter = db_fetch_all(
        "SELECT bs.full_name,
                COUNT(b.id) AS count,
                COUNT(CASE WHEN b.status='completed' THEN 1 END) AS completed
         FROM bookings b
         LEFT JOIN babysitters bs ON bs.id = b.babysitter_id
         WHERE b.service_date BETWEEN ? AND ?
         GROUP BY b.babysitter_id, bs.full_name
         ORDER BY completed DESC",
        [$date_start, $date_end]
    );

    $company_name = get_setting('company_name', 'Faiza Kids Concierge');
    $logo         = get_setting('company_logo', '');

    // Serve HTML for PDF printing
    header('Content-Type: text/html; charset=UTF-8');

    $date_start_fr = format_date_fr($date_start);
    $date_end_fr   = format_date_fr($date_end);

    echo '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport — ' . htmlspecialchars($company_name) . '</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 30px; }
  h1   { color: #c8953a; }
  h2   { color: #444; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th   { background: #c8953a; color: #fff; padding: 7px 10px; text-align: left; }
  td   { padding: 6px 10px; border-bottom: 1px solid #eee; }
  tr:nth-child(even) td { background: #f9f6f0; }
  .kpi-grid { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 24px; }
  .kpi { background: #f5f0e8; border-radius: 8px; padding: 16px 22px; min-width: 140px; }
  .kpi .val { font-size: 24px; font-weight: bold; color: #c8953a; }
  .kpi .lbl { font-size: 11px; color: #666; margin-top: 4px; }
  @media print { body { margin: 10px; } }
</style>
</head>
<body>
<h1>' . htmlspecialchars($company_name) . ' — Rapport</h1>
<p>Période : <strong>' . htmlspecialchars($date_start_fr) . '</strong> au <strong>' . htmlspecialchars($date_end_fr) . '</strong></p>
<p style="color:#888;font-size:11px;">Généré le ' . date('d/m/Y à H:i') . '</p>

<h2>Indicateurs clés</h2>
<div class="kpi-grid">
  <div class="kpi"><div class="val">' . (int)$stats['total'] . '</div><div class="lbl">Réservations</div></div>
  <div class="kpi"><div class="val">' . (int)$stats['completed'] . '</div><div class="lbl">Terminées</div></div>
  <div class="kpi"><div class="val">' . (int)$stats['cancelled'] . '</div><div class="lbl">Annulées</div></div>
  <div class="kpi"><div class="val">' . number_format((float)$stats['revenue'], 0, ',', ' ') . ' DH</div><div class="lbl">Chiffre d\'affaires validé</div></div>
  <div class="kpi"><div class="val">' . format_duration((int)round((float)$stats['avg_duration'])) . '</div><div class="lbl">Durée moyenne</div></div>
  <div class="kpi"><div class="val">' . number_format((float)$stats['avg_price'], 0, ',', ' ') . ' DH</div><div class="lbl">Prix moyen</div></div>
</div>

<h2>Chiffre d\'affaires par hôtel</h2>
<table>
  <thead><tr><th>Hôtel</th><th>Ville</th><th>Réservations</th><th>CA validé (DH)</th></tr></thead>
  <tbody>';

    foreach ($by_hotel as $row) {
        echo '<tr>
          <td>' . htmlspecialchars($row['hotel_name'] ?? 'Non défini') . '</td>
          <td>' . htmlspecialchars($row['city'] ?? '') . '</td>
          <td>' . (int)$row['count'] . '</td>
          <td>' . number_format((float)$row['revenue'], 0, ',', ' ') . ' DH</td>
        </tr>';
    }

    echo '</tbody></table>

<h2>Performance des babysitters</h2>
<table>
  <thead><tr><th>Babysitter</th><th>Réservations</th><th>Terminées</th></tr></thead>
  <tbody>';

    foreach ($by_babysitter as $row) {
        echo '<tr>
          <td>' . htmlspecialchars($row['full_name'] ?? 'Non assignée') . '</td>
          <td>' . (int)$row['count'] . '</td>
          <td>' . (int)$row['completed'] . '</td>
        </tr>';
    }

    echo '</tbody></table>

<script>window.onload = function() { window.print(); };</script>
</body></html>';
    exit;
}
