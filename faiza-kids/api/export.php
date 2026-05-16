<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$action = $_GET['action'] ?? 'bookings_csv';
$format = $_GET['format'] ?? 'csv';
$date_start = $_GET['date_start'] ?? date('Y-m-01');
$date_end   = $_GET['date_end']   ?? date('Y-m-d');

// Validate and sanitize date params
$date_start = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_start) ? $date_start : date('Y-m-01');
$date_end   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_end)   ? $date_end   : date('Y-m-d');

$company_name = get_setting('company_name', 'Faiza Kids Concierge');

function csv_row(array $cols): string {
    return implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $cols)) . "\r\n";
}

function send_csv(string $filename, string $content): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo $content;
    exit;
}

function send_pdf_html(string $title, string $html): void {
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

// ─── BOOKINGS CSV ───────────────────────────────────────────────────────────
if ($action === 'bookings_csv') {
    $rows = db_fetch_all(
        "SELECT b.reference, b.type, b.status, b.payment_status,
                b.service_date, b.start_time, b.duration_minutes, b.children_count,
                b.client_name, b.client_phone, b.client_email,
                h.name as hotel_name, b.room_number,
                b.city, b.area, b.address,
                b.price_amount, b.babysitter_id,
                bs.name as babysitter_name,
                b.notes, b.created_at
         FROM bookings b
         LEFT JOIN hotels h ON b.hotel_id = h.id
         LEFT JOIN babysitters bs ON b.babysitter_id = bs.id
         WHERE b.service_date BETWEEN ? AND ?
         ORDER BY b.service_date DESC, b.start_time DESC",
        [$date_start, $date_end]
    );

    $out  = csv_row(['Référence','Type','Statut','Statut paiement','Date service','Heure','Durée (min)','Nb enfants',
                     'Client','Téléphone','Email','Hôtel','Chambre','Ville','Quartier','Adresse',
                     'Prix (DH)','Babysitter','Notes','Créé le']);

    foreach ($rows as $r) {
        $out .= csv_row([
            $r['reference'], $r['type'], $r['status'], $r['payment_status'] ?? '',
            $r['service_date'], $r['start_time'], $r['duration_minutes'] ?? '', $r['children_count'],
            $r['client_name'], $r['client_phone'], $r['client_email'] ?? '',
            $r['hotel_name'] ?? '', $r['room_number'] ?? '',
            $r['city'] ?? '', $r['area'] ?? '', $r['address'] ?? '',
            $r['price_amount'] ?? '', $r['babysitter_name'] ?? '',
            $r['notes'] ?? '', $r['created_at'],
        ]);
    }

    send_csv("reservations_{$date_start}_{$date_end}.csv", $out);
}

// ─── PAYMENTS CSV ────────────────────────────────────────────────────────────
if ($action === 'payments_csv') {
    $rows = db_fetch_all(
        "SELECT b.reference, b.client_name, b.client_phone,
                h.name as hotel_name, b.service_date,
                b.price_amount, b.payment_status,
                pp.original_name as proof_file, pp.created_at as proof_date
         FROM bookings b
         LEFT JOIN hotels h ON b.hotel_id = h.id
         LEFT JOIN payment_proofs pp ON pp.booking_id = b.id
         WHERE b.service_date BETWEEN ? AND ?
           AND b.payment_status != 'not_required'
         ORDER BY b.service_date DESC",
        [$date_start, $date_end]
    );

    $out = csv_row(['Référence','Client','Téléphone','Hôtel','Date service','Montant (DH)','Statut paiement','Preuve reçue le']);
    foreach ($rows as $r) {
        $out .= csv_row([
            $r['reference'], $r['client_name'], $r['client_phone'],
            $r['hotel_name'] ?? '', $r['service_date'],
            $r['price_amount'] ?? '', $r['payment_status'] ?? '',
            $r['proof_date'] ?? '',
        ]);
    }

    send_csv("paiements_{$date_start}_{$date_end}.csv", $out);
}

// ─── REPORT PDF (print-ready HTML) ──────────────────────────────────────────
if ($action === 'report_pdf') {
    $stats = db_fetch(
        "SELECT COUNT(*) as total,
                SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN payment_status='validated' THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN payment_status='validated' THEN price_amount ELSE 0 END) as revenue
         FROM bookings WHERE service_date BETWEEN ? AND ?",
        [$date_start, $date_end]
    );

    $by_hotel = db_fetch_all(
        "SELECT h.name, COUNT(b.id) as bookings, SUM(b.price_amount) as revenue
         FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.id
         WHERE b.service_date BETWEEN ? AND ?
         GROUP BY b.hotel_id ORDER BY bookings DESC",
        [$date_start, $date_end]
    );

    $recent = db_fetch_all(
        "SELECT b.reference, b.client_name, b.service_date, b.start_time, b.status,
                b.price_amount, h.name as hotel_name
         FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.id
         WHERE b.service_date BETWEEN ? AND ?
         ORDER BY b.service_date DESC LIMIT 30",
        [$date_start, $date_end]
    );

    $status_labels = ['pending'=>'En attente','confirmed'=>'Confirmée','in_progress'=>'En cours','completed'=>'Terminée','cancelled'=>'Annulée'];

    $generated = date('d/m/Y H:i');

    $hotel_rows = '';
    foreach ($by_hotel as $h) {
        $hotel_rows .= '<tr><td>' . htmlspecialchars($h['name'] ?? 'N/A') . '</td>'
            . '<td style="text-align:center">' . (int)$h['bookings'] . '</td>'
            . '<td style="text-align:right">' . number_format((float)$h['revenue'], 0, '.', ' ') . ' DH</td></tr>';
    }

    $booking_rows = '';
    foreach ($recent as $b) {
        $booking_rows .= '<tr>'
            . '<td style="font-family:monospace">' . htmlspecialchars($b['reference']) . '</td>'
            . '<td>' . htmlspecialchars($b['client_name']) . '</td>'
            . '<td>' . htmlspecialchars($b['hotel_name'] ?? '–') . '</td>'
            . '<td>' . htmlspecialchars($b['service_date']) . ' ' . htmlspecialchars($b['start_time']) . '</td>'
            . '<td>' . htmlspecialchars($status_labels[$b['status']] ?? $b['status']) . '</td>'
            . '<td style="text-align:right">' . ($b['price_amount'] ? number_format((float)$b['price_amount'], 0, '.', ' ') . ' DH' : '–') . '</td>'
            . '</tr>';
    }

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport – {$company_name}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Helvetica Neue',Arial,sans-serif;font-size:12px;color:#1A2E24;background:#fff;padding:24px}
h1{font-size:22px;color:#0D2B1D;margin-bottom:4px}
.sub{font-size:12px;color:#6B7A72;margin-bottom:24px}
.period{background:#F0FAF5;border:1px solid #A7F3D0;border-radius:6px;display:inline-block;padding:4px 12px;font-size:12px;color:#065F46;margin-bottom:24px}
.kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:28px}
.kpi{background:#F8FAF9;border:1px solid #E5EDE9;border-radius:8px;padding:12px;text-align:center}
.kpi-val{font-size:22px;font-weight:700;color:#059669}
.kpi-lab{font-size:10px;color:#6B7A72;margin-top:2px}
h2{font-size:14px;font-weight:700;color:#0D2B1D;margin:20px 0 10px;padding-bottom:6px;border-bottom:2px solid #0D2B1D}
table{width:100%;border-collapse:collapse;margin-bottom:20px}
th{background:#0D2B1D;color:#52B788;font-size:10px;font-weight:600;padding:7px 10px;text-align:left}
td{padding:7px 10px;border-bottom:1px solid #E5EDE9;font-size:11px}
tr:nth-child(even) td{background:#F8FAF9}
.footer{margin-top:24px;font-size:10px;color:#9CA3AF;border-top:1px solid #E5EDE9;padding-top:12px;display:flex;justify-content:space-between}
@media print{body{padding:0}@page{margin:1cm}}
</style>
</head>
<body>
<h1>{$company_name}</h1>
<div class="sub">Rapport de performance · Généré le {$generated}</div>
<div class="period">📅 Période : {$date_start} → {$date_end}</div>

<div class="kpis">
  <div class="kpi"><div class="kpi-val">{$stats['total']}</div><div class="kpi-lab">Total</div></div>
  <div class="kpi"><div class="kpi-val">{$stats['confirmed']}</div><div class="kpi-lab">Confirmées</div></div>
  <div class="kpi"><div class="kpi-val">{$stats['completed']}</div><div class="kpi-lab">Terminées</div></div>
  <div class="kpi"><div class="kpi-val">{$stats['cancelled']}</div><div class="kpi-lab">Annulées</div></div>
  <div class="kpi"><div class="kpi-val">{$stats['paid']}</div><div class="kpi-lab">Payées</div></div>
  <div class="kpi"><div class="kpi-val">{$stats['revenue']} DH</div><div class="kpi-lab">Revenus</div></div>
</div>

<h2>Répartition par hôtel</h2>
<table>
  <thead><tr><th>Hôtel</th><th style="text-align:center">Réservations</th><th style="text-align:right">Revenus</th></tr></thead>
  <tbody>{$hotel_rows}</tbody>
</table>

<h2>Dernières réservations (30)</h2>
<table>
  <thead><tr><th>Référence</th><th>Client</th><th>Hôtel</th><th>Date / Heure</th><th>Statut</th><th style="text-align:right">Prix</th></tr></thead>
  <tbody>{$booking_rows}</tbody>
</table>

<div class="footer">
  <span>{$company_name}</span>
  <span>Rapport du {$date_start} au {$date_end}</span>
</div>
<script>window.onload=()=>window.print();</script>
</body>
</html>
HTML;

    send_pdf_html("Rapport {$date_start} – {$date_end}", $html);
}

// ─── BOOKING DETAIL PDF ──────────────────────────────────────────────────────
if ($action === 'booking_pdf') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); exit('Missing id'); }

    $b = db_fetch(
        "SELECT b.*, h.name as hotel_name, h.code as hotel_code, bs.name as babysitter_name
         FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.id LEFT JOIN babysitters bs ON b.babysitter_id=bs.id
         WHERE b.id=?",
        [$id]
    );
    if (!$b) { http_response_code(404); exit('Not found'); }

    $children = db_fetch_all("SELECT * FROM children WHERE booking_id=? ORDER BY id", [$id]);
    $status_labels = ['pending'=>'En attente','confirmed'=>'Confirmée','in_progress'=>'En cours','completed'=>'Terminée','cancelled'=>'Annulée'];
    $payment_labels = ['not_required'=>'Non requis','pending'=>'En attente','requested'=>'Demandé','proof_uploaded'=>'Preuve reçue','validated'=>'Validé','refused'=>'Refusé'];

    $child_rows = '';
    foreach ($children as $i => $c) {
        $child_rows .= '<tr><td>' . ($i+1) . '</td><td>' . htmlspecialchars($c['first_name'] ?? '–') . '</td><td>' . htmlspecialchars($c['age'] ?? '–') . ' ans</td><td>' . htmlspecialchars($c['allergies'] ?? '–') . '</td></tr>';
    }

    $location_val = $b['type'] === 'hotel'
        ? htmlspecialchars($b['hotel_name'] ?? '') . ($b['room_number'] ? ' · Ch. ' . htmlspecialchars($b['room_number']) : '')
        : htmlspecialchars(trim(($b['city'] ?? '') . ' ' . ($b['area'] ?? '') . ' ' . ($b['address'] ?? '')));

    $generated = date('d/m/Y H:i');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Réservation {$b['reference']}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Helvetica Neue',Arial,sans-serif;font-size:12px;color:#1A2E24;background:#fff;padding:24px}
.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:16px;border-bottom:3px solid #0D2B1D}
.logo-name{font-size:20px;font-weight:700;color:#0D2B1D}
.logo-sub{font-size:11px;color:#6B7A72;margin-top:2px}
.ref-box{text-align:right}
.ref-val{font-size:20px;font-weight:700;color:#059669;font-family:monospace}
.ref-date{font-size:11px;color:#6B7A72;margin-top:2px}
.status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;background:#D1FAE5;color:#065F46}
.section{margin-bottom:20px}
.section-title{font-size:13px;font-weight:700;color:#0D2B1D;padding-bottom:6px;border-bottom:2px solid #0D2B1D;margin-bottom:12px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.field{padding:8px 10px;background:#F8FAF9;border-radius:6px}
.field-label{font-size:9px;font-weight:600;color:#6B7A72;text-transform:uppercase;letter-spacing:.5px}
.field-value{font-size:12px;color:#1A2E24;margin-top:3px;font-weight:500}
table{width:100%;border-collapse:collapse}
th{background:#0D2B1D;color:#52B788;font-size:10px;padding:6px 10px;text-align:left}
td{padding:7px 10px;border-bottom:1px solid #E5EDE9;font-size:11px}
.footer{margin-top:24px;font-size:10px;color:#9CA3AF;text-align:center;padding-top:12px;border-top:1px solid #E5EDE9}
@media print{body{padding:0}@page{margin:1cm}}
</style>
</head>
<body>
<div class="header">
  <div><div class="logo-name">{$company_name}</div><div class="logo-sub">Babysitting Concierge – Agadir / Taghazout</div></div>
  <div class="ref-box">
    <div class="ref-val">{$b['reference']}</div>
    <div class="ref-date">Créé le {$b['created_at']}</div>
    <div class="status-badge">{$status_labels[$b['status']]}</div>
  </div>
</div>

<div class="section">
  <div class="section-title">Informations client</div>
  <div class="grid">
    <div class="field"><div class="field-label">Nom</div><div class="field-value">{$b['client_name']}</div></div>
    <div class="field"><div class="field-label">Téléphone</div><div class="field-value">{$b['client_phone']}</div></div>
    <div class="field"><div class="field-label">Email</div><div class="field-value">{$b['client_email']}</div></div>
    <div class="field"><div class="field-label">Langue</div><div class="field-value">{$b['client_lang']}</div></div>
  </div>
</div>

<div class="section">
  <div class="section-title">Détails de la prestation</div>
  <div class="grid">
    <div class="field"><div class="field-label">Type</div><div class="field-value">{$b['type']}</div></div>
    <div class="field"><div class="field-label">Lieu</div><div class="field-value">{$location_val}</div></div>
    <div class="field"><div class="field-label">Date</div><div class="field-value">{$b['service_date']}</div></div>
    <div class="field"><div class="field-label">Heure</div><div class="field-value">{$b['start_time']}</div></div>
    <div class="field"><div class="field-label">Durée</div><div class="field-value">{$b['duration_minutes']} min</div></div>
    <div class="field"><div class="field-label">Nb enfants</div><div class="field-value">{$b['children_count']}</div></div>
    <div class="field"><div class="field-label">Prix</div><div class="field-value">{$b['price_amount']} DH</div></div>
    <div class="field"><div class="field-label">Paiement</div><div class="field-value">{$payment_labels[$b['payment_status']]}</div></div>
    <div class="field"><div class="field-label">Babysitter</div><div class="field-value">{$b['babysitter_name']}</div></div>
  </div>
</div>

HTML;

    if ($children) {
        $html .= <<<CHILD
<div class="section">
  <div class="section-title">Enfants ({$b['children_count']})</div>
  <table>
    <thead><tr><th>#</th><th>Prénom</th><th>Âge</th><th>Allergies / Notes</th></tr></thead>
    <tbody>{$child_rows}</tbody>
  </table>
</div>
CHILD;
    }

    if (!empty($b['notes'])) {
        $notes_safe = htmlspecialchars($b['notes']);
        $html .= <<<NOTES
<div class="section">
  <div class="section-title">Notes</div>
  <div style="padding:10px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;font-size:12px;white-space:pre-line">{$notes_safe}</div>
</div>
NOTES;
    }

    $html .= <<<FOOT
<div class="footer">{$company_name} · Document généré le {$generated}</div>
<script>window.onload=()=>window.print();</script>
</body>
</html>
FOOT;

    send_pdf_html("Réservation {$b['reference']}", $html);
}

// ─── FALLBACK ────────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['error' => 'Unknown action: ' . $action]);
