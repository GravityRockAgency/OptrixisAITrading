<?php
/**
 * Faiza Kids Concierge — Gestion des réservations
 */

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$page_title    = 'Réservations';
$page_subtitle = 'Gestion des demandes hôtel, City et communication client.';

// ── Filters from GET ─────────────────────────────────────────────────────────
$filter_type       = $_GET['type']       ?? '';
$filter_status     = $_GET['status']     ?? '';
$filter_date_start = $_GET['date_start'] ?? '';
$filter_date_end   = $_GET['date_end']   ?? '';
$filter_hotel      = $_GET['hotel']      ?? '';
$filter_search     = $_GET['search']     ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

// ── Build query ───────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($filter_type)   { $where[] = 'b.type = ?';         $params[] = $filter_type; }
if ($filter_status) { $where[] = 'b.status = ?';        $params[] = $filter_status; }
if ($filter_hotel)  { $where[] = 'b.hotel_id = ?';      $params[] = (int)$filter_hotel; }
if ($filter_date_start) { $where[] = 'b.service_date >= ?'; $params[] = $filter_date_start; }
if ($filter_date_end)   { $where[] = 'b.service_date <= ?'; $params[] = $filter_date_end; }
if ($filter_search) {
    $where[]  = '(b.reference LIKE ? OR b.client_name LIKE ? OR b.client_whatsapp LIKE ? OR b.room_number LIKE ? OR h.name LIKE ?)';
    $like      = '%' . $filter_search . '%';
    $params    = array_merge($params, [$like, $like, $like, $like, $like]);
}

$where_sql = implode(' AND ', $where);

// Count total
$total_row = db_fetch(
    "SELECT COUNT(*) as c FROM bookings b LEFT JOIN hotels h ON b.hotel_id = h.id WHERE $where_sql",
    $params
);
$total = (int)($total_row['c'] ?? 0);

// Paginate
$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Fetch bookings
$bookings = db_fetch_all(
    "SELECT b.*, h.name as hotel_name, h.code as hotel_code,
            bs.full_name as babysitter_name
     FROM bookings b
     LEFT JOIN hotels h  ON b.hotel_id = h.id
     LEFT JOIN babysitters bs ON b.babysitter_id = bs.id
     WHERE $where_sql
     ORDER BY b.created_at DESC
     LIMIT $per_page OFFSET $offset",
    $params
);

// Hotels for filter dropdown
$hotels = db_fetch_all("SELECT id, name FROM hotels WHERE is_active = 1 ORDER BY sort_order, name", []);

// Build URL helper for preserving filters
function bookings_url(array $overrides = []): string {
    $defaults = [
        'type'       => $_GET['type']       ?? '',
        'status'     => $_GET['status']     ?? '',
        'date_start' => $_GET['date_start'] ?? '',
        'date_end'   => $_GET['date_end']   ?? '',
        'hotel'      => $_GET['hotel']      ?? '',
        'search'     => $_GET['search']     ?? '',
        'page'       => $_GET['page']       ?? 1,
    ];
    $merged = array_merge($defaults, $overrides);
    $q = http_build_query(array_filter($merged, fn($v) => $v !== '' && $v !== 0 && $v !== '0'));
    return '/admin/bookings' . ($q ? '?' . $q : '');
}

include 'layout-top.php';

// Status options
$status_options = [
    'new'         => 'Nouvelle demande',
    'pending'     => 'En attente',
    'confirmed'   => 'Confirmée',
    'in_progress' => 'En cours',
    'completed'   => 'Terminée',
    'cancelled'   => 'Annulée',
    'no_show'     => 'No-show',
];
?>

<style>
/* ─── Bookings page styles ───────────────────────────────────────── */
.fk-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
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
.fk-header-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* Filter card */
.fk-card {
    background: #fff;
    border: 1px solid var(--fk-border, #E5EDE9);
    border-radius: var(--fk-radius, 12px);
    padding: 20px 22px;
    box-shadow: var(--fk-shadow);
    margin-bottom: 20px;
}
.fk-filter-card { padding: 18px 22px; }

.fk-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-end;
}
.fk-filter-row + .fk-filter-row { margin-top: 12px; }
.fk-filter-group { display: flex; flex-direction: column; gap: 5px; }
.fk-filter-label {
    font-size: .68rem;
    font-weight: 600;
    color: var(--fk-muted, #6B7A72);
    text-transform: uppercase;
    letter-spacing: .06em;
}
.fk-select {
    padding: 8px 12px;
    border: 1px solid var(--fk-border, #E5EDE9);
    border-radius: 8px;
    font-size: .8rem;
    font-family: inherit;
    color: var(--fk-text, #1A2E24);
    background: #fff;
    outline: none;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    height: 36px;
}
.fk-select:focus { border-color: #52B788; box-shadow: 0 0 0 3px rgba(82,183,136,.12); }
.fk-input-sm {
    padding: 7px 12px;
    border: 1px solid var(--fk-border, #E5EDE9);
    border-radius: 8px;
    font-size: .8rem;
    font-family: inherit;
    color: var(--fk-text, #1A2E24);
    background: #fff;
    outline: none;
    height: 36px;
    transition: border-color .15s, box-shadow .15s;
}
.fk-input-sm:focus { border-color: #52B788; box-shadow: 0 0 0 3px rgba(82,183,136,.12); }

/* Type pills */
.fk-type-pills { display: flex; gap: 6px; }
.fk-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: .76rem;
    font-weight: 600;
    border: 1.5px solid var(--fk-border, #E5EDE9);
    color: var(--fk-muted, #6B7A72);
    cursor: pointer;
    text-decoration: none;
    transition: all .15s;
    background: #fff;
}
.fk-pill:hover { border-color: #52B788; color: #2D6A4F; background: rgba(82,183,136,.05); }
.fk-pill.fk-pill-active { background: #52B788; border-color: #52B788; color: #fff; }
.fk-pill.fk-pill-hotel.fk-pill-active { background: #2D6A4F; border-color: #2D6A4F; }
.fk-pill.fk-pill-city.fk-pill-active  { background: #3B82F6; border-color: #3B82F6; }

/* Results count */
.fk-results-count {
    font-size: .78rem;
    color: var(--fk-muted, #6B7A72);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.fk-results-count strong { color: var(--fk-text, #1A2E24); font-weight: 700; }

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
    font-size: .65rem;
    font-weight: 600;
    color: var(--fk-muted, #6B7A72);
    text-transform: uppercase;
    letter-spacing: .07em;
    border-bottom: 1px solid var(--fk-border, #E5EDE9);
    white-space: nowrap;
    background: var(--fk-light-bg, #F8FAF9);
}
.fk-table th:first-child { border-radius: 8px 0 0 0; }
.fk-table th:last-child  { border-radius: 0 8px 0 0; }
.fk-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--fk-border, #E5EDE9);
    color: var(--fk-text, #1A2E24);
    white-space: nowrap;
}
.fk-table tbody tr:last-child td { border-bottom: none; }
.fk-table tbody tr:hover td { background: rgba(248,250,249,.8); }

/* Badges */
.fk-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .67rem;
    font-weight: 600;
    white-space: nowrap;
}
.badge-new        { background: #EFF6FF; color: #1E40AF; }
.badge-pending    { background: #FFFBEB; color: #92400E; }
.badge-confirmed  { background: #ECFDF5; color: #065F46; }
.badge-inprogress { background: #F0FDF4; color: #166534; }
.badge-in_progress { background: #F0FDF4; color: #166534; }
.badge-completed  { background: #D1FAE5; color: #065F46; }
.badge-cancelled  { background: #FEF2F2; color: #991B1B; }
.badge-noshow     { background: #FEF3C7; color: #92400E; }
.badge-no_show    { background: #FEF3C7; color: #92400E; }
.badge-default    { background: #F3F4F6; color: #374151; }
.badge-hotel { background: rgba(82,183,136,.12); color: #1A5235; }
.badge-city  { background: rgba(59,130,246,.12); color: #1E3A6E; }
.badge-price-pending { background: #FFF7ED; color: #9A3412; font-size: .63rem; }

/* Action buttons */
.fk-actions { display: flex; align-items: center; gap: 5px; }
.fk-icon-btn {
    width: 30px; height: 30px;
    border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; border: 1px solid var(--fk-border, #E5EDE9);
    background: #fff; color: var(--fk-muted, #6B7A72);
    text-decoration: none;
    transition: background .14s, color .14s, border-color .14s;
    flex-shrink: 0;
}
.fk-icon-btn:hover { background: var(--fk-light-bg, #F8FAF9); color: var(--fk-text,#1A2E24); }
.fk-icon-btn-wa { color: #25D166; border-color: rgba(37,209,102,.3); }
.fk-icon-btn-wa:hover { background: rgba(37,209,102,.08); }
.fk-icon-btn-pdf { color: #EF4444; border-color: rgba(239,68,68,.2); }
.fk-icon-btn-pdf:hover { background: rgba(239,68,68,.06); }

/* WA link */
.fk-wa-link {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .72rem; color: #25D166;
    text-decoration: none; font-weight: 500;
}
.fk-wa-link:hover { text-decoration: underline; }

/* Ref */
.fk-ref {
    font-family: 'Courier New', monospace;
    font-size: .72rem; font-weight: 600;
    color: var(--fk-btn, #52B788);
    text-decoration: none;
}
.fk-ref:hover { text-decoration: underline; }

/* Client cell */
.fk-client-name { font-weight: 600; font-size: .78rem; }
.fk-client-sub  { font-size: .68rem; color: var(--fk-muted, #6B7A72); }

/* Pagination */
.fk-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
    margin-top: 20px;
    flex-wrap: wrap;
}
.fk-page-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 34px; height: 34px;
    border-radius: 8px;
    border: 1px solid var(--fk-border, #E5EDE9);
    background: #fff; color: var(--fk-text, #1A2E24);
    font-size: .78rem; font-weight: 500;
    text-decoration: none; padding: 0 10px;
    transition: all .15s;
}
.fk-page-btn:hover { border-color: #52B788; color: #2D6A4F; background: rgba(82,183,136,.05); }
.fk-page-btn.fk-page-active { background: #52B788; border-color: #52B788; color: #fff; font-weight: 700; }
.fk-page-btn.fk-page-disabled { opacity: .4; pointer-events: none; }
.fk-page-ellipsis { color: var(--fk-muted); font-size: .78rem; padding: 0 4px; }

/* Empty state */
.fk-empty {
    text-align: center;
    padding: 48px 20px;
}
.fk-empty-icon {
    width: 56px; height: 56px;
    background: var(--fk-light-bg, #F8FAF9);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
    color: var(--fk-muted, #6B7A72);
}
.fk-empty h3 { font-size: .92rem; font-weight: 700; color: var(--fk-text, #1A2E24); margin-bottom: 6px; }
.fk-empty p  { font-size: .8rem; color: var(--fk-muted, #6B7A72); }

/* Btn */
.fk-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 9px;
    font-size: .82rem; font-weight: 600;
    cursor: pointer; border: none; text-decoration: none;
    transition: background .15s, box-shadow .15s, transform .1s;
    white-space: nowrap; font-family: inherit;
}
.fk-btn:active { transform: translateY(1px); }
.fk-btn-primary   { background: #52B788; color: #fff; }
.fk-btn-primary:hover { background: #3d9e70; }
.fk-btn-secondary {
    background: var(--fk-light-bg, #F8FAF9);
    color: var(--fk-text, #1A2E24);
    border: 1px solid var(--fk-border, #E5EDE9);
}
.fk-btn-secondary:hover { background: #eef3f0; }
.fk-btn-sm { padding: 6px 12px; font-size: .75rem; }

/* Price cell */
.fk-price-val { font-weight: 700; font-size: .8rem; }
.fk-price-pending { font-size: .68rem; color: var(--fk-muted); font-style: italic; }

/* Duration */
.fk-duration { font-size: .7rem; color: var(--fk-muted); }
</style>

<!-- ═══════════════════════════ PAGE HEADER ═══════════════════════════ -->
<div class="fk-page-header">
    <div>
        <h1 class="fk-page-title">Réservations</h1>
        <p class="fk-page-subtitle"><?= htmlspecialchars($page_subtitle, ENT_QUOTES) ?></p>
    </div>
    <div class="fk-header-actions">
        <a href="/admin/bookings/export<?= $filter_type || $filter_status || $filter_date_start || $filter_search ? '?' . http_build_query(array_filter(['type'=>$filter_type,'status'=>$filter_status,'date_start'=>$filter_date_start,'date_end'=>$filter_date_end,'search'=>$filter_search])) : '' ?>"
           class="fk-btn fk-btn-secondary">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Exporter CSV
        </a>
        <a href="/admin/manual-booking" class="fk-btn fk-btn-primary">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M12 4v16m-8-8h16" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Nouvelle réservation
        </a>
    </div>
</div>

<!-- ═══════════════════════════ FILTRES ═══════════════════════════ -->
<div class="fk-card fk-filter-card">
    <form method="GET" action="/admin/bookings" id="filterForm">

        <!-- Row 1: Type pills + Statut + Dates -->
        <div class="fk-filter-row">

            <!-- Type pills -->
            <div class="fk-filter-group">
                <span class="fk-filter-label">Type</span>
                <div class="fk-type-pills">
                    <a href="<?= bookings_url(['type' => '', 'page' => 1]) ?>"
                       class="fk-pill<?= $filter_type === '' ? ' fk-pill-active' : '' ?>">
                        Toutes
                    </a>
                    <a href="<?= bookings_url(['type' => 'hotel', 'page' => 1]) ?>"
                       class="fk-pill fk-pill-hotel<?= $filter_type === 'hotel' ? ' fk-pill-active' : '' ?>">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 21V8l9-5 9 5v13H3z" stroke-linejoin="round"/><path d="M9 21v-6h6v6"/>
                        </svg>
                        Hôtel
                    </a>
                    <a href="<?= bookings_url(['type' => 'city', 'page' => 1]) ?>"
                       class="fk-pill fk-pill-city<?= $filter_type === 'city' ? ' fk-pill-active' : '' ?>">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/>
                        </svg>
                        City
                    </a>
                </div>
            </div>

            <!-- Statut -->
            <div class="fk-filter-group">
                <label class="fk-filter-label" for="filterStatus">Statut</label>
                <select class="fk-select" id="filterStatus" name="status" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <?php foreach ($status_options as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $filter_status === $val ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Hôtel -->
            <?php if (!empty($hotels)): ?>
            <div class="fk-filter-group">
                <label class="fk-filter-label" for="filterHotel">Hôtel</label>
                <select class="fk-select" id="filterHotel" name="hotel" onchange="this.form.submit()">
                    <option value="">Tous les hôtels</option>
                    <?php foreach ($hotels as $h): ?>
                    <option value="<?= (int)$h['id'] ?>" <?= (int)$filter_hotel === (int)$h['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($h['name'], ENT_QUOTES) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Date début -->
            <div class="fk-filter-group">
                <label class="fk-filter-label" for="filterDateStart">Date début</label>
                <input type="date" class="fk-input-sm" id="filterDateStart" name="date_start"
                       value="<?= htmlspecialchars($filter_date_start, ENT_QUOTES) ?>">
            </div>

            <!-- Date fin -->
            <div class="fk-filter-group">
                <label class="fk-filter-label" for="filterDateEnd">Date fin</label>
                <input type="date" class="fk-input-sm" id="filterDateEnd" name="date_end"
                       value="<?= htmlspecialchars($filter_date_end, ENT_QUOTES) ?>">
            </div>
        </div>

        <!-- Row 2: Recherche + Boutons -->
        <div class="fk-filter-row" style="margin-top:12px;">
            <div class="fk-filter-group" style="flex:1;min-width:220px;">
                <label class="fk-filter-label" for="filterSearch">Recherche</label>
                <div style="position:relative;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--fk-muted);" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35" stroke-linecap="round"/>
                    </svg>
                    <input type="text" class="fk-input-sm" id="filterSearch" name="search"
                           style="padding-left:32px;width:100%;max-width:380px;"
                           placeholder="Référence, client, téléphone, chambre…"
                           value="<?= htmlspecialchars($filter_search, ENT_QUOTES) ?>">
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <!-- Hidden type input so form submit preserves pill selection -->
                <?php if ($filter_type): ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($filter_type, ENT_QUOTES) ?>">
                <?php endif; ?>
                <button type="submit" class="fk-btn fk-btn-primary fk-btn-sm">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35" stroke-linecap="round"/>
                    </svg>
                    Filtrer
                </button>
                <a href="/admin/bookings" class="fk-btn fk-btn-secondary fk-btn-sm">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/>
                    </svg>
                    Réinitialiser
                </a>
            </div>
        </div>

    </form>
</div>

<!-- ═══════════════════════════ RÉSULTATS ═══════════════════════════ -->
<div class="fk-results-count">
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <strong><?= number_format($total) ?></strong> réservation<?= $total > 1 ? 's' : '' ?> trouvée<?= $total > 1 ? 's' : '' ?>
    <?php if ($filter_type || $filter_status || $filter_date_start || $filter_date_end || $filter_search): ?>
        <span style="color:var(--fk-muted);">· Filtres actifs</span>
    <?php endif; ?>
    <?php if ($total > $per_page): ?>
        <span style="color:var(--fk-muted);">· Page <?= $page ?> sur <?= $total_pages ?></span>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════ TABLE ═══════════════════════════ -->
<div class="fk-card" style="padding:0;overflow:hidden;">

    <?php if (empty($bookings)): ?>
    <div class="fk-empty">
        <div class="fk-empty-icon">
            <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3>Aucune réservation trouvée</h3>
        <p>
            <?php if ($filter_type || $filter_status || $filter_date_start || $filter_date_end || $filter_search): ?>
                Modifiez vos filtres ou
                <a href="/admin/bookings" style="color:#52B788;text-decoration:none;font-weight:600;">réinitialisez la recherche</a>.
            <?php else: ?>
                Commencez par créer une réservation manuelle.
            <?php endif; ?>
        </p>
    </div>

    <?php else: ?>
    <div class="fk-table-wrap">
        <table class="fk-table" aria-label="Liste des réservations">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Lieu</th>
                    <th>Client</th>
                    <th>WhatsApp</th>
                    <th>Date &amp; heure</th>
                    <th>Enfants</th>
                    <th>Prix / Devis</th>
                    <th>Babysitter</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b):
                $wa_phone = $b['client_whatsapp'] ?? '';
                $wa_name  = $b['client_name'] ?? '';
                $wa_ref   = $b['reference'] ?? ('#' . $b['id']);
                $wa_date  = !empty($b['service_date']) ? format_date_fr($b['service_date']) : '';
                $wa_time  = !empty($b['start_time'])   ? substr($b['start_time'], 0, 5)     : '';
                $wa_msg   = "Bonjour {$wa_name}, concernant votre réservation Faiza Kids {$wa_ref} du {$wa_date}" . ($wa_time ? " à {$wa_time}" : "") . ".";
                $wa_url   = get_whatsapp_link($wa_phone);

                $lieu = $b['type'] === 'hotel'
                    ? ($b['hotel_name'] ?? ($b['city'] ?? '—'))
                    : (($b['area'] ? $b['area'] . ', ' : '') . ($b['city'] ?? '—'));

                $status_cls = 'badge-' . str_replace('_', '', ($b['status'] ?? 'default'));
            ?>
            <tr>
                <!-- Référence -->
                <td>
                    <a href="/admin/bookings/view/<?= (int)$b['id'] ?>" class="fk-ref">
                        <?= htmlspecialchars($wa_ref, ENT_QUOTES) ?>
                    </a>
                </td>

                <!-- Type -->
                <td>
                    <span class="fk-badge <?= ($b['type'] ?? '') === 'hotel' ? 'badge-hotel' : 'badge-city' ?>">
                        <?php if (($b['type'] ?? '') === 'hotel'): ?>
                            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21V8l9-5 9 5v13H3z" stroke-linejoin="round"/></svg>
                            Hôtel
                        <?php else: ?>
                            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0L6.343 16.657a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg>
                            City
                        <?php endif; ?>
                    </span>
                </td>

                <!-- Statut -->
                <td>
                    <span class="fk-badge <?= $status_cls ?>">
                        <?= htmlspecialchars(get_status_label($b['status'] ?? ''), ENT_QUOTES) ?>
                    </span>
                </td>

                <!-- Lieu -->
                <td>
                    <span title="<?= htmlspecialchars($lieu, ENT_QUOTES) ?>" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;display:block;">
                        <?= htmlspecialchars(mb_strimwidth($lieu, 0, 24, '…'), ENT_QUOTES) ?>
                    </span>
                    <?php if (!empty($b['room_number'])): ?>
                    <div class="fk-client-sub">Chambre <?= htmlspecialchars($b['room_number'], ENT_QUOTES) ?></div>
                    <?php endif; ?>
                </td>

                <!-- Client -->
                <td>
                    <div class="fk-client-name"><?= htmlspecialchars(mb_strimwidth($wa_name, 0, 22, '…'), ENT_QUOTES) ?></div>
                    <?php if (!empty($b['client_email'])): ?>
                    <div class="fk-client-sub"><?= htmlspecialchars(mb_strimwidth($b['client_email'], 0, 22, '…'), ENT_QUOTES) ?></div>
                    <?php endif; ?>
                </td>

                <!-- WhatsApp -->
                <td>
                    <?php if ($wa_phone): ?>
                    <a href="<?= htmlspecialchars($wa_url, ENT_QUOTES) ?>" class="fk-wa-link" target="_blank" rel="noopener noreferrer" title="Ouvrir WhatsApp">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                            <path d="M12 2C6.477 2 2 6.477 2 12c0 1.76.458 3.413 1.258 4.851L2 22l5.293-1.238A9.942 9.942 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.09-1.125l-.294-.175-3.05.712.752-2.982-.192-.31A7.974 7.974 0 014 12c0-4.418 3.582-8 8-8s8 3.582 8 8-3.582 8-8 8z"/>
                        </svg>
                        <?= htmlspecialchars($wa_phone, ENT_QUOTES) ?>
                    </a>
                    <?php else: ?>
                        <span style="color:var(--fk-muted);font-size:.72rem;">—</span>
                    <?php endif; ?>
                </td>

                <!-- Date & heure -->
                <td>
                    <?php if (!empty($b['service_date'])): ?>
                    <div style="font-size:.78rem;font-weight:600;">
                        <?= htmlspecialchars(format_date_fr($b['service_date']), ENT_QUOTES) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($b['start_time'])): ?>
                    <div class="fk-client-sub">
                        <?= htmlspecialchars(substr($b['start_time'], 0, 5), ENT_QUOTES) ?>
                        <?php if (!empty($b['duration_minutes'])): ?>
                            · <?= htmlspecialchars(format_duration((int)$b['duration_minutes']), ENT_QUOTES) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Enfants -->
                <td style="text-align:center;">
                    <span style="display:inline-flex;align-items:center;gap:3px;font-size:.78rem;font-weight:600;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke-linecap="round"/>
                        </svg>
                        <?= (int)($b['children_count'] ?? 1) ?>
                    </span>
                </td>

                <!-- Prix / Devis -->
                <td>
                    <?php if (!empty($b['final_price'])): ?>
                        <span class="fk-price-val"><?= htmlspecialchars(format_price((float)$b['final_price']), ENT_QUOTES) ?></span>
                    <?php elseif (!empty($b['suggested_price'])): ?>
                        <span class="fk-badge badge-price-pending">Devis: <?= htmlspecialchars(format_price((float)$b['suggested_price']), ENT_QUOTES) ?></span>
                    <?php else: ?>
                        <span class="fk-price-pending">Devis en attente</span>
                    <?php endif; ?>
                </td>

                <!-- Babysitter -->
                <td>
                    <?php if (!empty($b['babysitter_name'])): ?>
                        <span style="font-size:.78rem;font-weight:500;"><?= htmlspecialchars(mb_strimwidth($b['babysitter_name'], 0, 20, '…'), ENT_QUOTES) ?></span>
                    <?php else: ?>
                        <span style="color:var(--fk-muted);font-size:.72rem;">Non assignée</span>
                    <?php endif; ?>
                </td>

                <!-- Actions -->
                <td>
                    <div class="fk-actions">
                        <a href="/admin/bookings/view/<?= (int)$b['id'] ?>"
                           class="fk-icon-btn" title="Voir la réservation">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                        <?php if ($wa_phone): ?>
                        <button class="fk-icon-btn fk-icon-btn-wa"
                                title="Envoyer via WhatsApp"
                                onclick="fkOpenWa(<?= json_encode($wa_phone) ?>, <?= json_encode($wa_msg) ?>)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                                <path d="M12 2C6.477 2 2 6.477 2 12c0 1.76.458 3.413 1.258 4.851L2 22l5.293-1.238A9.942 9.942 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.09-1.125l-.294-.175-3.05.712.752-2.982-.192-.31A7.974 7.974 0 014 12c0-4.418 3.582-8 8-8s8 3.582 8 8-3.582 8-8 8z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        <a href="/admin/bookings/pdf/<?= (int)$b['id'] ?>"
                           class="fk-icon-btn fk-icon-btn-pdf" title="Télécharger PDF" target="_blank" rel="noopener">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/>
                                <path d="M14 2v6h6M9 15h6M9 11h6M9 7h2" stroke-linecap="round"/>
                            </svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════ PAGINATION ═══════════════════════════ -->
<?php if ($total_pages > 1): ?>
<div class="fk-pagination" role="navigation" aria-label="Pagination">
    <!-- Précédent -->
    <?php if ($page > 1): ?>
    <a href="<?= bookings_url(['page' => $page - 1]) ?>" class="fk-page-btn" aria-label="Page précédente">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
    <?php else: ?>
    <span class="fk-page-btn fk-page-disabled" aria-disabled="true">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <?php endif; ?>

    <!-- Pages -->
    <?php
    $range     = 2;
    $start     = max(1, $page - $range);
    $end       = min($total_pages, $page + $range);
    if ($start > 1): ?>
        <a href="<?= bookings_url(['page' => 1]) ?>" class="fk-page-btn">1</a>
        <?php if ($start > 2): ?><span class="fk-page-ellipsis">…</span><?php endif; ?>
    <?php endif;
    for ($p = $start; $p <= $end; $p++): ?>
    <a href="<?= bookings_url(['page' => $p]) ?>"
       class="fk-page-btn<?= $p === $page ? ' fk-page-active' : '' ?>"
       <?= $p === $page ? 'aria-current="page"' : '' ?>>
        <?= $p ?>
    </a>
    <?php endfor;
    if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?><span class="fk-page-ellipsis">…</span><?php endif; ?>
        <a href="<?= bookings_url(['page' => $total_pages]) ?>" class="fk-page-btn"><?= $total_pages ?></a>
    <?php endif; ?>

    <!-- Suivant -->
    <?php if ($page < $total_pages): ?>
    <a href="<?= bookings_url(['page' => $page + 1]) ?>" class="fk-page-btn" aria-label="Page suivante">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
    <?php else: ?>
    <span class="fk-page-btn fk-page-disabled" aria-disabled="true">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'layout-bottom.php'; ?>

<script>
(function () {
    'use strict';

    // Auto-submit date filter on change
    const dateInputs = document.querySelectorAll('#filterDateStart, #filterDateEnd');
    dateInputs.forEach(function(inp) {
        inp.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });

})();
</script>
