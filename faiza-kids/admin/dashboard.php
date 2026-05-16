<?php
/**
 * Faiza Kids Concierge — Tableau de bord
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$page_title    = 'Tableau de bord';
$page_subtitle = 'Vue opérationnelle · ' . date('d/m/Y');

// ── Date references ─────────────────────────────────────────────────────────
$today       = date('Y-m-d');
$month_start = date('Y-m-01');
$year_start  = date('Y-01-01');

// ── KPI Queries ─────────────────────────────────────────────────────────────
$today_bookings = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE service_date = ?",
    [$today]
)['cnt'] ?? 0);

$pending_bookings = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE status IN ('new', 'pending')",
    []
)['cnt'] ?? 0);

$today_revenue = (float)(db_fetch(
    "SELECT COALESCE(SUM(final_price), 0) as total FROM bookings
     WHERE service_date = ? AND status IN ('confirmed','completed') AND final_price IS NOT NULL",
    [$today]
)['total'] ?? 0);

$monthly_revenue = (float)(db_fetch(
    "SELECT COALESCE(SUM(final_price), 0) as total FROM bookings
     WHERE service_date >= ? AND status IN ('confirmed','completed') AND final_price IS NOT NULL",
    [$month_start]
)['total'] ?? 0);

$pending_payments = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE payment_status IN ('requested', 'proof_sent')",
    []
)['cnt'] ?? 0);

$cancelled = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings
     WHERE status IN ('cancelled','no_show') AND service_date >= ?",
    [$month_start]
)['cnt'] ?? 0);

$hotel_requests = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE type = 'hotel' AND status IN ('new','pending')",
    []
)['cnt'] ?? 0);

$city_requests = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE type = 'city' AND status IN ('new','pending')",
    []
)['cnt'] ?? 0);

// ── Weekly revenue (last 7 days) ─────────────────────────────────────────────
$weekly_revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $rev = (float)(db_fetch(
        "SELECT COALESCE(SUM(final_price), 0) as total FROM bookings
         WHERE service_date = ? AND status IN ('confirmed','completed') AND final_price IS NOT NULL",
        [$day]
    )['total'] ?? 0);
    $weekly_revenue[] = [
        'date'   => $day,
        'label'  => date('d/m', strtotime($day)),
        'amount' => $rev,
    ];
}

// ── Channel split this month ─────────────────────────────────────────────────
$hotel_count = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE type = 'hotel' AND service_date >= ?",
    [$month_start]
)['cnt'] ?? 0);

$city_count = (int)(db_fetch(
    "SELECT COUNT(*) as cnt FROM bookings WHERE type = 'city' AND service_date >= ?",
    [$month_start]
)['cnt'] ?? 0);

$total_channel = $hotel_count + $city_count;
$hotel_pct = $total_channel > 0 ? round(($hotel_count / $total_channel) * 100) : 0;
$city_pct  = $total_channel > 0 ? 100 - $hotel_pct : 0;

// ── Today's bookings (focus du jour) ────────────────────────────────────────
$focus_today = db_fetch_all(
    "SELECT b.*, h.name as hotel_name, bs.full_name as babysitter_name
     FROM bookings b
     LEFT JOIN hotels h ON b.hotel_id = h.id
     LEFT JOIN babysitters bs ON b.babysitter_id = bs.id
     WHERE b.service_date = ? AND b.status NOT IN ('cancelled','no_show')
     ORDER BY b.start_time ASC",
    [$today]
);

// ── Revenue per hotel this month ─────────────────────────────────────────────
$hotel_revenue = db_fetch_all(
    "SELECT h.name, h.code,
            COALESCE(SUM(b.final_price), 0) as revenue,
            COUNT(b.id) as bookings
     FROM hotels h
     LEFT JOIN bookings b ON b.hotel_id = h.id
                          AND b.service_date >= ?
                          AND b.status IN ('confirmed','completed')
                          AND b.final_price IS NOT NULL
     WHERE h.is_active = 1
     GROUP BY h.id
     ORDER BY revenue DESC
     LIMIT 8",
    [$month_start]
);

// Compute max revenue for progress bars
$max_hotel_rev = !empty($hotel_revenue) ? max(array_column($hotel_revenue, 'revenue')) : 1;
if ($max_hotel_rev == 0) $max_hotel_rev = 1;

// ── Latest 10 bookings ────────────────────────────────────────────────────────
$latest_bookings = db_fetch_all(
    "SELECT b.*, h.name as hotel_name, h.code as hotel_code, bs.full_name as babysitter_name
     FROM bookings b
     LEFT JOIN hotels h ON b.hotel_id = h.id
     LEFT JOIN babysitters bs ON b.babysitter_id = bs.id
     ORDER BY b.created_at DESC
     LIMIT 10",
    []
);

// ── Summary stats for weekly chart ───────────────────────────────────────────
$weekly_total = array_sum(array_column($weekly_revenue, 'amount'));

include 'layout-top.php';
?>

<style>
/* ─── Dashboard specific styles ──────────────────────────────────────────── */
.fk-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.fk-page-title {
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--fk-text, #1A2E24);
    letter-spacing: -.02em;
    line-height: 1.2;
}
.fk-page-subtitle {
    font-size: .8rem;
    color: var(--fk-muted, #6B7A72);
    margin-top: 4px;
}
.fk-flex { display: flex; }
.fk-gap-2 { gap: 10px; }
.fk-items-center { align-items: center; }

/* KPI Grid */
.fk-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 1100px) { .fk-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px)  { .fk-kpi-grid { grid-template-columns: 1fr; } }

.fk-card {
    background: var(--fk-card-bg, #fff);
    border: 1px solid var(--fk-border, #E5EDE9);
    border-radius: var(--fk-radius, 12px);
    padding: 20px 22px;
    box-shadow: var(--fk-shadow);
}
.fk-kpi-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: box-shadow .18s, transform .18s;
}
.fk-kpi-card:hover { box-shadow: var(--fk-shadow-md); transform: translateY(-1px); }

.fk-kpi-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}
.fk-kpi-icon {
    width: 42px; height: 42px;
    border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.fk-kpi-icon-green  { background: rgba(82,183,136,.13); color: #2D6A4F; }
.fk-kpi-icon-amber  { background: rgba(245,158,11,.13);  color: #92400E; }
.fk-kpi-icon-blue   { background: rgba(59,130,246,.13);  color: #1E40AF; }
.fk-kpi-icon-purple { background: rgba(139,92,246,.13);  color: #5B21B6; }
.fk-kpi-icon-orange { background: rgba(249,115,22,.13);  color: #9A3412; }
.fk-kpi-icon-red    { background: rgba(239,68,68,.13);   color: #991B1B; }
.fk-kpi-icon-teal   { background: rgba(20,184,166,.13);  color: #134E4A; }
.fk-kpi-icon-gold   { background: rgba(232,195,66,.18);  color: #78521A; }

.fk-kpi-value {
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--fk-text, #1A2E24);
    letter-spacing: -.03em;
    line-height: 1;
}
.fk-kpi-value.fk-kpi-warn { color: #D97706; }
.fk-kpi-value.fk-kpi-danger { color: #EF4444; }

.fk-kpi-label {
    font-size: .75rem;
    font-weight: 500;
    color: var(--fk-muted, #6B7A72);
}
.fk-kpi-trend {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: .7rem;
    font-weight: 600;
    margin-top: 2px;
}
.fk-trend-up   { color: #16A34A; }
.fk-trend-down { color: #DC2626; }
.fk-trend-neutral { color: var(--fk-muted, #6B7A72); }

/* Two-column row */
.fk-row-2col {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .fk-row-2col { grid-template-columns: 1fr; } }
.fk-row-2col-eq {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .fk-row-2col-eq { grid-template-columns: 1fr; } }

.fk-card-title {
    font-size: .9rem;
    font-weight: 700;
    color: var(--fk-text, #1A2E24);
    margin-bottom: 4px;
}
.fk-card-subtitle {
    font-size: .73rem;
    color: var(--fk-muted, #6B7A72);
    margin-bottom: 18px;
}
.fk-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 18px;
    gap: 12px;
}
.fk-chart-wrap {
    position: relative;
    width: 100%;
    height: 220px;
}

/* Channel visual */
.fk-channel-split {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    height: 100%;
    padding: 10px 0;
}
.fk-donut-wrap {
    position: relative;
    width: 140px; height: 140px;
    flex-shrink: 0;
}
.fk-donut-wrap canvas { width: 100% !important; height: 100% !important; }
.fk-donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.fk-donut-center strong { font-size: 1.35rem; font-weight: 800; color: var(--fk-text,#1A2E24); }
.fk-donut-center span { font-size: .65rem; color: var(--fk-muted,#6B7A72); font-weight: 500; }
.fk-channel-legend { display: flex; flex-direction: column; gap: 10px; width: 100%; }
.fk-legend-item {
    display: flex; align-items: center; gap: 10px;
    font-size: .78rem;
}
.fk-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.fk-legend-info { flex: 1; }
.fk-legend-name { font-weight: 600; color: var(--fk-text,#1A2E24); }
.fk-legend-pct { font-size: .7rem; color: var(--fk-muted,#6B7A72); }
.fk-legend-count { font-size: .75rem; font-weight: 700; color: var(--fk-text,#1A2E24); }

/* Table */
.fk-table-wrap { overflow-x: auto; }
.fk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .78rem;
}
.fk-table th {
    text-align: left;
    padding: 9px 12px;
    font-size: .68rem;
    font-weight: 600;
    color: var(--fk-muted,#6B7A72);
    text-transform: uppercase;
    letter-spacing: .06em;
    border-bottom: 1px solid var(--fk-border,#E5EDE9);
    white-space: nowrap;
    background: var(--fk-light-bg,#F8FAF9);
}
.fk-table th:first-child { border-radius: 8px 0 0 0; }
.fk-table th:last-child  { border-radius: 0 8px 0 0; }
.fk-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--fk-border,#E5EDE9);
    color: var(--fk-text,#1A2E24);
    white-space: nowrap;
}
.fk-table tbody tr:last-child td { border-bottom: none; }
.fk-table tbody tr:hover td { background: var(--fk-light-bg,#F8FAF9); }

/* Badges */
.fk-badge {
    display: inline-flex; align-items: center;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .68rem;
    font-weight: 600;
    white-space: nowrap;
}
.badge-new        { background: #EFF6FF; color: #1E40AF; }
.badge-pending    { background: #FFFBEB; color: #92400E; }
.badge-confirmed  { background: #ECFDF5; color: #065F46; }
.badge-inprogress { background: #F0FDF4; color: #166534; }
.badge-completed  { background: #F0FDF4; color: #166534; }
.badge-cancelled  { background: #FEF2F2; color: #991B1B; }
.badge-noshow     { background: #FEF3C7; color: #92400E; }
.badge-default    { background: #F3F4F6; color: #374151; }
.badge-hotel { background: rgba(82,183,136,.12); color: #1A5235; }
.badge-city  { background: rgba(59,130,246,.12); color: #1E3A6E; }

/* Focus table action buttons */
.fk-actions { display: flex; align-items: center; gap: 6px; }
.fk-icon-btn {
    width: 30px; height: 30px;
    border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; border: 1px solid var(--fk-border,#E5EDE9);
    background: #fff; color: var(--fk-muted,#6B7A72);
    text-decoration: none; transition: background .14s, color .14s, border-color .14s;
    flex-shrink: 0;
}
.fk-icon-btn:hover { background: var(--fk-light-bg,#F8FAF9); color: var(--fk-text,#1A2E24); }
.fk-icon-btn-wa  { color: #25D166; border-color: rgba(37,209,102,.3); }
.fk-icon-btn-wa:hover { background: rgba(37,209,102,.08); }

/* Empty state */
.fk-empty {
    text-align: center;
    padding: 36px 20px;
}
.fk-empty-icon {
    width: 52px; height: 52px;
    background: var(--fk-light-bg,#F8FAF9);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 12px;
    color: var(--fk-muted,#6B7A72);
}
.fk-empty p { font-size: .82rem; color: var(--fk-muted,#6B7A72); }

/* Hotel revenue progress */
.fk-hotel-list { display: flex; flex-direction: column; gap: 14px; }
.fk-hotel-row { display: flex; flex-direction: column; gap: 5px; }
.fk-hotel-row-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.fk-hotel-row-top span:first-child {
    font-size: .78rem; font-weight: 600; color: var(--fk-text,#1A2E24);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.fk-hotel-row-top span:last-child { font-size: .75rem; font-weight: 700; color: var(--fk-text,#1A2E24); white-space: nowrap; }
.fk-hotel-meta { font-size: .68rem; color: var(--fk-muted,#6B7A72); }
.fk-progress { height: 6px; background: var(--fk-border,#E5EDE9); border-radius: 3px; overflow: hidden; }
.fk-progress-bar { height: 100%; background: linear-gradient(90deg, #52B788, #2D6A4F); border-radius: 3px; transition: width .6s ease; }

/* Time display */
.fk-time-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .72rem; font-weight: 600;
    color: var(--fk-text,#1A2E24);
    background: var(--fk-light-bg,#F8FAF9);
    border: 1px solid var(--fk-border,#E5EDE9);
    border-radius: 6px; padding: 3px 8px;
    white-space: nowrap;
}

/* Client name */
.fk-client-name { font-weight: 600; }
.fk-client-phone { font-size: .68rem; color: var(--fk-muted,#6B7A72); }

/* Ref link */
.fk-ref { font-family: 'Courier New', monospace; font-size: .72rem; font-weight: 600; color: var(--fk-btn,#52B788); }

/* Section heading */
.fk-section-heading {
    font-size: .82rem;
    font-weight: 700;
    color: var(--fk-text,#1A2E24);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.fk-section-heading::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--fk-border,#E5EDE9);
}
</style>

<!-- ═══════════════════════════ PAGE HEADER ═══════════════════════════ -->
<div class="fk-page-header">
    <div>
        <h1 class="fk-page-title">Tableau de bord</h1>
        <p class="fk-page-subtitle"><?= htmlspecialchars($page_subtitle, ENT_QUOTES) ?></p>
    </div>
    <div class="fk-flex fk-gap-2 fk-items-center">
        <button class="fk-btn fk-btn-secondary" onclick="location.reload()">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="M4 4v5h.582m0 0A8.001 8.001 0 0120 12M4.582 9H9m11 11v-5h-.581m0 0A8.001 8.001 0 014 12m15.419 4H15" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Actualiser
        </button>
        <a href="/admin/manual-booking" class="fk-btn fk-btn-primary">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M12 4v16m-8-8h16" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Nouvelle réservation
        </a>
    </div>
</div>

<!-- ═══════════════════════════ KPI CARDS ═══════════════════════════ -->
<div class="fk-kpi-grid">

    <!-- Réservations du jour -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value"><?= $today_bookings ?></div>
                <div class="fk-kpi-label">Réservations du jour</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-green">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2.5" fill="none"/>
                    <path d="M8 2v4M16 2v4M3 9h18" stroke-linecap="round"/>
                    <path d="M8 13h.01M12 13h.01M16 13h.01" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend fk-trend-neutral">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14" stroke-linecap="round"/></svg>
            <?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- Demandes en attente -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value<?= $pending_bookings > 0 ? ' fk-kpi-warn' : '' ?>"><?= $pending_bookings ?></div>
                <div class="fk-kpi-label">Demandes en attente</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-amber">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" fill="none"/>
                    <path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend <?= $pending_bookings > 0 ? 'fk-trend-down' : 'fk-trend-up' ?>">
            <?php if ($pending_bookings > 0): ?>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12l7 7 7-7" stroke-linecap="round"/></svg>
            Nécessite une action
            <?php else: ?>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            File vide
            <?php endif; ?>
        </div>
    </div>

    <!-- Revenu du jour -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value"><?= sanitize(format_price($today_revenue)) ?></div>
                <div class="fk-kpi-label">Revenu du jour</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-blue">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z" fill="none"/>
                    <path d="M15 9a3 3 0 11-6 0M15 15a3 3 0 11-6 0M12 6v2M12 16v2" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend fk-trend-neutral">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14" stroke-linecap="round"/></svg>
            Confirmées & terminées
        </div>
    </div>

    <!-- Revenu mensuel -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value"><?= sanitize(format_price($monthly_revenue)) ?></div>
                <div class="fk-kpi-label">Revenu mensuel</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-gold">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M3 17l6-6 4 4 8-8M21 7h-4V3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend fk-trend-up">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14m-7-7l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?= date('F Y', strtotime($month_start)) ?>
        </div>
    </div>

    <!-- Paiements en attente -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value<?= $pending_payments > 0 ? ' fk-kpi-warn' : '' ?>"><?= $pending_payments ?></div>
                <div class="fk-kpi-label">Paiements en attente</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-orange">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="2" y="5" width="20" height="14" rx="2.5" fill="none"/>
                    <path d="M2 10h20" stroke-linecap="round"/>
                    <path d="M6 15h4" stroke-linecap="round" stroke-width="2"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend <?= $pending_payments > 0 ? 'fk-trend-down' : 'fk-trend-up' ?>">
            <?php if ($pending_payments > 0): ?>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            Vérification requise
            <?php else: ?>
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            À jour
            <?php endif; ?>
        </div>
    </div>

    <!-- Annulées / No-show -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value<?= $cancelled > 0 ? ' fk-kpi-danger' : '' ?>"><?= $cancelled ?></div>
                <div class="fk-kpi-label">Annulées / No-show</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-red">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" fill="none"/>
                    <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend fk-trend-neutral">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14" stroke-linecap="round"/></svg>
            Ce mois-ci
        </div>
    </div>

    <!-- Demandes hôtels -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value"><?= $hotel_requests ?></div>
                <div class="fk-kpi-label">Demandes hôtels</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-teal">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M3 21V8l9-5 9 5v13H3z" stroke-linejoin="round" fill="none"/>
                    <path d="M9 21v-6h6v6" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend fk-trend-neutral">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14" stroke-linecap="round"/></svg>
            Nouvelles &amp; en attente
        </div>
    </div>

    <!-- Demandes City -->
    <div class="fk-card fk-kpi-card">
        <div class="fk-kpi-top">
            <div>
                <div class="fk-kpi-value"><?= $city_requests ?></div>
                <div class="fk-kpi-label">Demandes City</div>
            </div>
            <div class="fk-kpi-icon fk-kpi-icon-purple">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" fill="none"/>
                    <circle cx="12" cy="11" r="3" fill="none"/>
                </svg>
            </div>
        </div>
        <div class="fk-kpi-trend fk-trend-neutral">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14" stroke-linecap="round"/></svg>
            Nouvelles &amp; en attente
        </div>
    </div>

</div>
<!-- END KPI CARDS -->

<!-- ═══════════════════════ CHARTS ROW ═══════════════════════ -->
<div class="fk-row-2col">

    <!-- Revenu hebdomadaire -->
    <div class="fk-card">
        <div class="fk-card-header">
            <div>
                <div class="fk-card-title">Revenu hebdomadaire</div>
                <div class="fk-card-subtitle">7 derniers jours · Total: <?= sanitize(format_price($weekly_total)) ?></div>
            </div>
        </div>
        <div class="fk-chart-wrap">
            <canvas id="weeklyChart"
                    data-labels='<?= htmlspecialchars(json_encode(array_column($weekly_revenue, 'label')), ENT_QUOTES) ?>'
                    data-values='<?= htmlspecialchars(json_encode(array_column($weekly_revenue, 'amount')), ENT_QUOTES) ?>'
                    aria-label="Graphique revenu hebdomadaire"
                    role="img">
            </canvas>
        </div>
    </div>

    <!-- Répartition par canal -->
    <div class="fk-card">
        <div class="fk-card-header">
            <div>
                <div class="fk-card-title">Répartition par canal</div>
                <div class="fk-card-subtitle">Ce mois · <?= $total_channel ?> réservation<?= $total_channel > 1 ? 's' : '' ?></div>
            </div>
        </div>
        <div class="fk-channel-split">
            <div class="fk-donut-wrap">
                <canvas id="channelChart"
                        data-values='<?= htmlspecialchars(json_encode([$hotel_count, $city_count]), ENT_QUOTES) ?>'
                        data-labels='<?= htmlspecialchars(json_encode(['Hôtel', 'City']), ENT_QUOTES) ?>'
                        aria-label="Graphique répartition canal"
                        role="img">
                </canvas>
                <div class="fk-donut-center">
                    <strong><?= $total_channel ?></strong>
                    <span>total</span>
                </div>
            </div>
            <div class="fk-channel-legend">
                <div class="fk-legend-item">
                    <div class="fk-legend-dot" style="background:#52B788;"></div>
                    <div class="fk-legend-info">
                        <div class="fk-legend-name">Hôtel</div>
                        <div class="fk-legend-pct"><?= $hotel_pct ?>% du total</div>
                    </div>
                    <div class="fk-legend-count"><?= $hotel_count ?></div>
                </div>
                <div class="fk-legend-item">
                    <div class="fk-legend-dot" style="background:#3B82F6;"></div>
                    <div class="fk-legend-info">
                        <div class="fk-legend-name">City</div>
                        <div class="fk-legend-pct"><?= $city_pct ?>% du total</div>
                    </div>
                    <div class="fk-legend-count"><?= $city_count ?></div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- END CHARTS ROW -->

<!-- ═══════════════════════ FOCUS DU JOUR ═══════════════════════ -->
<div class="fk-card" style="margin-bottom:24px;">
    <div class="fk-card-header">
        <div>
            <div class="fk-card-title">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px;" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Focus du jour
            </div>
            <div class="fk-card-subtitle">Réservations actives · <?= date('d/m/Y') ?></div>
        </div>
        <a href="/admin/bookings?date=<?= urlencode($today) ?>" class="fk-btn fk-btn-secondary fk-btn-sm">Voir tout</a>
    </div>

    <?php if (empty($focus_today)): ?>
    <div class="fk-empty">
        <div class="fk-empty-icon">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2.5" fill="none"/>
                <path d="M8 2v4M16 2v4M3 9h18" stroke-linecap="round"/>
            </svg>
        </div>
        <p>Aucune réservation prévue aujourd'hui.</p>
    </div>
    <?php else: ?>
    <div class="fk-table-wrap">
        <table class="fk-table" aria-label="Réservations du jour">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Lieu</th>
                    <th>Enfants</th>
                    <th>Babysitter</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($focus_today as $b): ?>
            <tr>
                <td>
                    <span class="fk-time-badge">
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l2 2" stroke-linecap="round"/></svg>
                        <?= htmlspecialchars(substr($b['start_time'] ?? '—', 0, 5), ENT_QUOTES) ?>
                    </span>
                </td>
                <td>
                    <div class="fk-client-name"><?= sanitize($b['client_name'] ?? '—') ?></div>
                    <?php if (!empty($b['client_phone'])): ?>
                    <div class="fk-client-phone"><?= sanitize($b['client_phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="fk-badge <?= $b['type'] === 'hotel' ? 'badge-hotel' : 'badge-city' ?>">
                        <?= $b['type'] === 'hotel' ? 'Hôtel' : 'City' ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($b['hotel_name'])): ?>
                        <span title="<?= sanitize($b['hotel_name']) ?>"><?= sanitize(truncate($b['hotel_name'], 22)) ?></span>
                    <?php elseif (!empty($b['location'])): ?>
                        <span title="<?= sanitize($b['location']) ?>"><?= sanitize(truncate($b['location'], 22)) ?></span>
                    <?php else: ?>
                        <span class="fk-muted">—</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <?= (int)($b['children_count'] ?? 1) ?>
                </td>
                <td>
                    <?php if (!empty($b['babysitter_name'])): ?>
                        <?= sanitize($b['babysitter_name']) ?>
                    <?php else: ?>
                        <span style="color:var(--fk-muted);font-size:.72rem;">Non assigné</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="fk-badge <?= get_status_badge_class($b['status'] ?? '') ?>">
                        <?= sanitize(get_status_label($b['status'] ?? '')) ?>
                    </span>
                </td>
                <td>
                    <div class="fk-actions">
                        <a href="/admin/bookings/view/<?= (int)$b['id'] ?>"
                           class="fk-icon-btn"
                           title="Voir la réservation">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                        <?php if (!empty($b['client_phone'])):
                            $wa_phone   = sanitize($b['client_phone']);
                            $wa_name    = sanitize($b['client_name'] ?? '');
                            $wa_time    = sanitize(substr($b['start_time'] ?? '', 0, 5));
                            $wa_date    = date('d/m/Y', strtotime($today));
                            $wa_msg     = "Bonjour {$wa_name}, concernant votre réservation Faiza Kids du {$wa_date} à {$wa_time}.";
                            $wa_onclick = "fkOpenWa(" . json_encode($wa_phone) . "," . json_encode($wa_msg) . ")";
                        ?>
                        <button class="fk-icon-btn fk-icon-btn-wa"
                                title="Envoyer un message WhatsApp"
                                onclick="<?= htmlspecialchars($wa_onclick, ENT_QUOTES) ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                                <path d="M11.998 2.003C6.484 2.003 2.003 6.484 2.003 12c0 1.76.458 3.413 1.258 4.851L2 22l5.293-1.238A9.942 9.942 0 0012 21.998c5.516 0 9.997-4.481 9.997-9.997C21.997 6.484 17.514 2.003 11.998 2.003zm0 18.207a8.206 8.206 0 01-4.187-1.149l-.3-.178-3.143.734.772-3.064-.195-.316A8.156 8.156 0 013.79 12c0-4.52 3.688-8.204 8.208-8.204 4.518 0 8.205 3.685 8.205 8.205-.001 4.519-3.688 8.209-8.205 8.209z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<!-- END FOCUS DU JOUR -->

<!-- ═══════════════════════ BOTTOM ROW ═══════════════════════ -->
<div class="fk-row-2col-eq">

    <!-- Revenu par hôtel -->
    <div class="fk-card">
        <div class="fk-card-header">
            <div>
                <div class="fk-card-title">Revenu par hôtel</div>
                <div class="fk-card-subtitle">Ce mois · confirmées &amp; terminées</div>
            </div>
            <a href="/admin/reports" class="fk-btn fk-btn-secondary fk-btn-sm">Rapport</a>
        </div>

        <?php if (empty($hotel_revenue)): ?>
        <div class="fk-empty">
            <div class="fk-empty-icon">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3 21V8l9-5 9 5v13H3z" fill="none"/>
                    <path d="M9 21v-6h6v6"/>
                </svg>
            </div>
            <p>Aucun hôtel actif.</p>
        </div>
        <?php else: ?>
        <div class="fk-hotel-list">
            <?php foreach ($hotel_revenue as $h): ?>
            <div class="fk-hotel-row">
                <div class="fk-hotel-row-top">
                    <span><?= sanitize($h['name']) ?><?= !empty($h['code']) ? ' <small style="color:var(--fk-muted);font-weight:400;">(' . sanitize($h['code']) . ')</small>' : '' ?></span>
                    <span><?= sanitize(format_price((float)$h['revenue'])) ?></span>
                </div>
                <div class="fk-hotel-meta"><?= (int)$h['bookings'] ?> réservation<?= $h['bookings'] > 1 ? 's' : '' ?></div>
                <div class="fk-progress">
                    <div class="fk-progress-bar" style="width:<?= round(($h['revenue'] / $max_hotel_rev) * 100) ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Dernières demandes -->
    <div class="fk-card">
        <div class="fk-card-header">
            <div>
                <div class="fk-card-title">Dernières demandes</div>
                <div class="fk-card-subtitle">10 plus récentes</div>
            </div>
            <a href="/admin/bookings" class="fk-btn fk-btn-secondary fk-btn-sm">Toutes</a>
        </div>

        <?php if (empty($latest_bookings)): ?>
        <div class="fk-empty">
            <div class="fk-empty-icon">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <p>Aucune réservation enregistrée.</p>
        </div>
        <?php else: ?>
        <div class="fk-table-wrap">
            <table class="fk-table" aria-label="Dernières demandes">
                <thead>
                    <tr>
                        <th>Réf.</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($latest_bookings as $b): ?>
                <tr>
                    <td>
                        <a href="/admin/bookings/view/<?= (int)$b['id'] ?>" class="fk-ref" title="Voir la réservation">
                            <?= sanitize($b['reference'] ?? '#' . $b['id']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="fk-badge <?= ($b['type'] ?? '') === 'hotel' ? 'badge-hotel' : 'badge-city' ?>">
                            <?= ($b['type'] ?? '') === 'hotel' ? 'Hôtel' : 'City' ?>
                        </span>
                    </td>
                    <td>
                        <span class="fk-badge <?= get_status_badge_class($b['status'] ?? '') ?>">
                            <?= sanitize(get_status_label($b['status'] ?? '')) ?>
                        </span>
                    </td>
                    <td>
                        <div class="fk-client-name" style="font-size:.75rem;"><?= sanitize(truncate($b['client_name'] ?? '—', 18)) ?></div>
                    </td>
                    <td>
                        <span style="font-size:.73rem;color:var(--fk-muted);">
                            <?= !empty($b['service_date']) ? date('d/m/Y', strtotime($b['service_date'])) : '—' ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size:.74rem;font-weight:600;">
                            <?= !empty($b['final_price']) ? sanitize(format_price((float)$b['final_price'])) : '<span style="color:var(--fk-muted)">—</span>' ?>
                        </span>
                    </td>
                    <td>
                        <div class="fk-actions">
                            <a href="/admin/bookings/view/<?= (int)$b['id'] ?>" class="fk-icon-btn" title="Voir">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                            <?php if (!empty($b['client_phone'])):
                                $lb_phone   = sanitize($b['client_phone']);
                                $lb_name    = sanitize($b['client_name'] ?? '');
                                $lb_ref     = sanitize($b['reference'] ?? '');
                                $lb_msg     = "Bonjour {$lb_name}, concernant votre réservation {$lb_ref}.";
                                $lb_onclick = "fkOpenWa(" . json_encode($lb_phone) . "," . json_encode($lb_msg) . ")";
                            ?>
                            <button class="fk-icon-btn fk-icon-btn-wa"
                                    title="WhatsApp"
                                    onclick="<?= htmlspecialchars($lb_onclick, ENT_QUOTES) ?>">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                                    <path d="M11.998 2.003C6.484 2.003 2.003 6.484 2.003 12c0 1.76.458 3.413 1.258 4.851L2 22l5.293-1.238A9.942 9.942 0 0012 21.998c5.516 0 9.997-4.481 9.997-9.997C21.997 6.484 17.514 2.003 11.998 2.003zm0 18.207a8.206 8.206 0 01-4.187-1.149l-.3-.178-3.143.734.772-3.064-.195-.316A8.156 8.156 0 013.79 12c0-4.52 3.688-8.204 8.208-8.204 4.518 0 8.205 3.685 8.205 8.205-.001 4.519-3.688 8.209-8.205 8.209z"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<!-- END BOTTOM ROW -->

<?php include 'layout-bottom.php'; ?>

<script>
(function () {
    'use strict';

    // ── Weekly bar chart ────────────────────────────────────────────────────
    const weeklyCanvas = document.getElementById('weeklyChart');
    if (weeklyCanvas && typeof Chart !== 'undefined') {
        const labels = JSON.parse(weeklyCanvas.dataset.labels || '[]');
        const values = JSON.parse(weeklyCanvas.dataset.values || '[]');
        new Chart(weeklyCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenu (DH)',
                    data: values,
                    backgroundColor: function(ctx) {
                        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 220);
                        gradient.addColorStop(0, 'rgba(82,183,136,.85)');
                        gradient.addColorStop(1, 'rgba(82,183,136,.25)');
                        return gradient;
                    },
                    borderColor: '#2D6A4F',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D2B1D',
                        titleColor: '#fff',
                        bodyColor: '#A7C5B4',
                        cornerRadius: 8,
                        padding: 10,
                        callbacks: {
                            label: function(ctx) {
                                return ' ' + ctx.parsed.y.toLocaleString('fr-MA') + ' DH';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 11, family: 'Inter' }, color: '#6B7A72' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(229,237,233,.7)', drawBorder: false },
                        border: { display: false, dash: [4, 4] },
                        ticks: {
                            font: { size: 11, family: 'Inter' },
                            color: '#6B7A72',
                            callback: function(v) { return v.toLocaleString('fr-MA') + ' DH'; },
                            maxTicksLimit: 5,
                        }
                    }
                },
                animation: { duration: 600, easing: 'easeOutQuart' }
            }
        });
    }

    // ── Channel donut chart ─────────────────────────────────────────────────
    const channelCanvas = document.getElementById('channelChart');
    if (channelCanvas && typeof Chart !== 'undefined') {
        const vals   = JSON.parse(channelCanvas.dataset.values || '[0,0]');
        const labels = JSON.parse(channelCanvas.dataset.labels || '["Hôtel","City"]');
        const hasData = vals.some(v => v > 0);
        new Chart(channelCanvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: hasData ? vals : [1, 0],
                    backgroundColor: hasData ? ['#52B788', '#3B82F6'] : ['#E5EDE9', '#E5EDE9'],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: hasData,
                        backgroundColor: '#0D2B1D',
                        titleColor: '#fff',
                        bodyColor: '#A7C5B4',
                        cornerRadius: 8,
                        padding: 10,
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ' ' + ctx.parsed + ' réservation(s) (' + pct + '%)';
                            }
                        }
                    }
                },
                animation: { animateRotate: true, duration: 700, easing: 'easeOutQuart' }
            }
        });
    }

})();
</script>
