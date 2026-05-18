<?php
/**
 * Faiza Kids Concierge — Admin Layout Top
 */

$current_path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$current_path = rtrim($current_path, '/') ?: '/admin';

function fk_nav_active(string $href, string $current): string {
    // Normalize both sides by stripping .php extension
    $current_norm = preg_replace('/\.php$/', '', $current);
    $href_norm    = preg_replace('/\.php$/', '', rtrim($href, '/')) ?: '/admin';
    if ($current_norm === $href_norm) return ' fk-nav-active';
    if ($href_norm !== '/admin' && str_starts_with($current_norm, $href_norm)) return ' fk-nav-active';
    if ($href_norm === '/admin' && $current_norm === '/admin/dashboard') return ' fk-nav-active';
    return '';
}

$admin_data = function_exists('get_admin') ? get_admin() : null;
$admin_name = $admin_data['full_name'] ?? ($_SESSION['admin_name'] ?? 'Admin');
$admin_email = $admin_data['email'] ?? ($_SESSION['admin_email'] ?? '');
$admin_initial = mb_strtoupper(mb_substr($admin_name, 0, 1));

$notif_count = 0;
try {
    $notif_row = db_fetch("SELECT COUNT(*) as cnt FROM notifications WHERE is_read = 0", []);
    $notif_count = (int)($notif_row['cnt'] ?? 0);
} catch (Exception $e) { /* table may not exist yet */ }

$page_title    = $page_title    ?? 'Tableau de bord';
$page_subtitle = $page_subtitle ?? '';
$extra_css     = $extra_css     ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES) ?> | Faiza Kids Concierge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/app.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>

    <?= $extra_css ?>

    <style>
        :root {
            --fk-sidebar-bg:      #0D2B1D;
            --fk-sidebar-active:  #2D6A4F;
            --fk-sidebar-hover:   #1A4532;
            --fk-sidebar-text:    rgba(255,255,255,.75);
            --fk-sidebar-text-active: #FFFFFF;
            --fk-sidebar-width:   260px;
            --fk-btn:             #52B788;
            --fk-btn-hover:       #3d9e70;
            --fk-light-bg:        #F8FAF9;
            --fk-card-bg:         #FFFFFF;
            --fk-border:          #E5EDE9;
            --fk-text:            #1A2E24;
            --fk-muted:           #6B7A72;
            --fk-gold:            #E8C342;
            --fk-topbar-h:        64px;
            --fk-radius:          12px;
            --fk-shadow:          0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
            --fk-shadow-md:       0 4px 16px rgba(0,0,0,.10);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 15px; }
        body.fk-admin {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--fk-light-bg);
            color: var(--fk-text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .fk-shell { display: flex; min-height: 100vh; }

        .fk-sidebar {
            width: var(--fk-sidebar-width);
            background: var(--fk-sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 200;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform .28s cubic-bezier(.4,0,.2,1);
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }
        .fk-sidebar::-webkit-scrollbar { width: 4px; }
        .fk-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }

        .fk-sidebar-logo {
            display: flex; align-items: center; gap: 12px;
            padding: 22px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            text-decoration: none;
        }
        .fk-logo-circle {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, #52B788, #2D6A4F);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800; color: #fff;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(82,183,136,.4);
        }
        .fk-logo-text { line-height: 1.2; }
        .fk-logo-text strong { display: block; font-size: .9rem; font-weight: 700; color: #FFFFFF; letter-spacing: -.01em; }
        .fk-logo-text span { font-size: .68rem; color: var(--fk-gold); font-weight: 500; letter-spacing: .05em; text-transform: uppercase; }

        .fk-nav { flex: 1; padding: 14px 12px; }
        .fk-nav-section { margin-bottom: 6px; }
        .fk-nav-section-label { font-size: .62rem; font-weight: 600; color: rgba(255,255,255,.3); letter-spacing: .1em; text-transform: uppercase; padding: 10px 10px 6px; }
        .fk-nav-item {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 12px; border-radius: 9px;
            text-decoration: none; color: var(--fk-sidebar-text);
            font-size: .82rem; font-weight: 500;
            transition: background .18s, color .18s;
            margin-bottom: 2px; position: relative; white-space: nowrap;
        }
        .fk-nav-item:hover { background: var(--fk-sidebar-hover); color: var(--fk-sidebar-text-active); }
        .fk-nav-item.fk-nav-active { background: var(--fk-sidebar-active); color: var(--fk-sidebar-text-active); font-weight: 600; }
        .fk-nav-item.fk-nav-active::before {
            content: ''; position: absolute; left: -12px; top: 50%; transform: translateY(-50%);
            width: 3px; height: 22px; background: #52B788; border-radius: 0 3px 3px 0;
        }
        .fk-nav-icon { width: 18px; height: 18px; flex-shrink: 0; opacity: .8; }
        .fk-nav-active .fk-nav-icon { opacity: 1; }

        .fk-sidebar-footer { padding: 14px 16px; border-top: 1px solid rgba(255,255,255,.07); }
        .fk-sidebar-user { display: flex; align-items: center; gap: 10px; }
        .fk-sidebar-avatar {
            width: 34px; height: 34px; background: var(--fk-sidebar-active);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .fk-sidebar-user-info { flex: 1; min-width: 0; }
        .fk-sidebar-user-name { font-size: .78rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fk-sidebar-logout { font-size: .7rem; color: rgba(255,255,255,.45); text-decoration: none; display: block; margin-top: 1px; }
        .fk-sidebar-logout:hover { color: #fc8181; }

        .fk-main { margin-left: var(--fk-sidebar-width); flex: 1; display: flex; flex-direction: column; min-height: 100vh; min-width: 0; }

        .fk-topbar {
            height: var(--fk-topbar-h); background: #FFFFFF;
            border-bottom: 1px solid var(--fk-border);
            display: flex; align-items: center; gap: 14px; padding: 0 24px;
            position: sticky; top: 0; z-index: 100; box-shadow: var(--fk-shadow);
        }
        .fk-topbar-left { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
        .fk-hamburger { display: none; background: none; border: none; cursor: pointer; padding: 6px; border-radius: 7px; color: var(--fk-muted); transition: background .15s; }
        .fk-hamburger:hover { background: var(--fk-light-bg); }
        .fk-breadcrumb { display: flex; align-items: center; gap: 6px; font-size: .8rem; color: var(--fk-muted); }
        .fk-breadcrumb-sep { opacity: .4; }
        .fk-breadcrumb-current { font-weight: 600; color: var(--fk-text); font-size: .82rem; }
        .fk-topbar-right { display: flex; align-items: center; gap: 10px; }

        .fk-search-bar {
            display: flex; align-items: center; gap: 8px;
            background: var(--fk-light-bg); border: 1px solid var(--fk-border);
            border-radius: 8px; padding: 7px 12px; min-width: 220px;
            transition: border-color .15s, box-shadow .15s;
        }
        .fk-search-bar:focus-within { border-color: #52B788; box-shadow: 0 0 0 3px rgba(82,183,136,.12); }
        .fk-search-bar svg { color: var(--fk-muted); flex-shrink: 0; }
        .fk-search-bar input { border: none; background: none; outline: none; font-size: .8rem; color: var(--fk-text); font-family: inherit; width: 100%; }
        .fk-search-bar input::placeholder { color: var(--fk-muted); }

        .fk-notif-btn { position: relative; background: none; border: none; cursor: pointer; padding: 8px; border-radius: 9px; color: var(--fk-muted); transition: background .15s, color .15s; }
        .fk-notif-btn:hover { background: var(--fk-light-bg); color: var(--fk-text); }
        .fk-notif-badge { position: absolute; top: 4px; right: 4px; min-width: 16px; height: 16px; background: #EF4444; border-radius: 8px; font-size: .6rem; font-weight: 700; color: #fff; display: flex; align-items: center; justify-content: center; border: 1.5px solid #fff; padding: 0 3px; }
        .fk-notif-badge:empty, .fk-notif-badge[data-count="0"] { display: none; }

        .fk-avatar-wrap { position: relative; }
        .fk-avatar-btn { display: flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer; padding: 4px 6px; border-radius: 9px; transition: background .15s; }
        .fk-avatar-btn:hover { background: var(--fk-light-bg); }
        .fk-topbar-avatar { width: 34px; height: 34px; background: linear-gradient(135deg, #52B788, #2D6A4F); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 700; color: #fff; }
        .fk-avatar-name { font-size: .8rem; font-weight: 600; color: var(--fk-text); }
        .fk-avatar-caret { color: var(--fk-muted); }
        .fk-avatar-dropdown { display: none; position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid var(--fk-border); border-radius: 10px; box-shadow: var(--fk-shadow-md); min-width: 200px; z-index: 300; overflow: hidden; }
        .fk-avatar-dropdown.fk-open { display: block; }
        .fk-avatar-dropdown-header { padding: 14px 16px 10px; border-bottom: 1px solid var(--fk-border); }
        .fk-avatar-dropdown-header strong { display: block; font-size: .83rem; font-weight: 600; color: var(--fk-text); }
        .fk-avatar-dropdown-header span { font-size: .72rem; color: var(--fk-muted); }
        .fk-dd-item { display: flex; align-items: center; gap: 10px; padding: 9px 16px; font-size: .8rem; color: var(--fk-text); text-decoration: none; transition: background .14s; }
        .fk-dd-item:hover { background: var(--fk-light-bg); }
        .fk-dd-item.fk-dd-danger { color: #EF4444; }
        .fk-dd-divider { border: none; border-top: 1px solid var(--fk-border); margin: 4px 0; }

        .fk-content { flex: 1; padding: 28px 28px 40px; min-width: 0; }

        .fk-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 190; }
        .fk-overlay.fk-open { display: block; }

        @media (max-width: 900px) {
            .fk-sidebar { transform: translateX(-100%); }
            .fk-sidebar.fk-sidebar-open { transform: translateX(0); }
            .fk-main { margin-left: 0; }
            .fk-hamburger { display: flex; }
            .fk-search-bar { min-width: 160px; }
            .fk-avatar-name { display: none; }
        }
        @media (max-width: 640px) {
            .fk-content { padding: 16px 14px 32px; }
            .fk-search-bar { display: none; }
        }
    </style>
</head>
<body class="fk-admin">

<div class="fk-overlay" id="fkOverlay" onclick="fkCloseSidebar()"></div>

<div class="fk-shell">

    <aside class="fk-sidebar" id="fkSidebar" role="navigation" aria-label="Navigation principale">

        <a href="/admin" class="fk-sidebar-logo">
            <div class="fk-logo-circle">F</div>
            <div class="fk-logo-text">
                <strong>Faiza Kids</strong>
                <span>Concierge</span>
            </div>
        </a>

        <nav class="fk-nav">

            <div class="fk-nav-section">
                <p class="fk-nav-section-label">Principal</p>

                <a href="/admin" class="fk-nav-item<?= fk_nav_active('/admin', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" fill="none"/>
                        <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" fill="none"/>
                        <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" fill="none"/>
                        <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" fill="none"/>
                    </svg>
                    Tableau de bord
                </a>

                <a href="/admin/calendar.php" class="fk-nav-item<?= fk_nav_active('/admin/calendar.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2.5" stroke="currentColor" fill="none"/>
                        <path d="M8 2v4M16 2v4M3 9h18" stroke="currentColor" stroke-linecap="round"/>
                        <path d="M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01M16 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                    Calendrier
                </a>
            </div>

            <div class="fk-nav-section">
                <p class="fk-nav-section-label">Réservations</p>

                <a href="/admin/bookings.php" class="fk-nav-item<?= fk_nav_active('/admin/bookings.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Réservations
                </a>

                <a href="/admin/manual-booking.php" class="fk-nav-item<?= fk_nav_active('/admin/manual-booking.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" fill="none"/>
                        <path d="M12 8v8M8 12h8" stroke="currentColor" stroke-linecap="round"/>
                    </svg>
                    Réservation manuelle
                </a>

                <a href="/admin/availability.php" class="fk-nav-item<?= fk_nav_active('/admin/availability.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" fill="none"/>
                        <path d="M12 7v5l3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Disponibilités
                </a>
            </div>

            <div class="fk-nav-section">
                <p class="fk-nav-section-label">Gestion</p>

                <a href="/admin/hotels.php" class="fk-nav-item<?= fk_nav_active('/admin/hotels.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 21V8l9-5 9 5v13H3z" stroke="currentColor" stroke-linejoin="round" fill="none"/>
                        <path d="M9 21v-6h6v6" stroke="currentColor" stroke-linejoin="round"/>
                        <path d="M9 9h.01M15 9h.01M9 13h.01M15 13h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Hôtels
                </a>

                <a href="/admin/babysitters.php" class="fk-nav-item<?= fk_nav_active('/admin/babysitters.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="7" r="3" stroke="currentColor" fill="none"/>
                        <path d="M3 20c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="currentColor" stroke-linecap="round" fill="none"/>
                        <circle cx="17" cy="7" r="2.5" stroke="currentColor" fill="none"/>
                        <path d="M21 20c0-2.761-1.791-5-4-5" stroke="currentColor" stroke-linecap="round"/>
                    </svg>
                    Babysitters
                </a>

                <a href="/admin/whatsapp.php" class="fk-nav-item<?= fk_nav_active('/admin/whatsapp.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M21 11.5C21 16.19 16.97 20 12 20a9.25 9.25 0 01-4.255-1.03L3 20l1.073-4.596A8.748 8.748 0 013 11.5C3 6.81 7.03 3 12 3s9 3.81 9 8.5z" stroke="currentColor" fill="none" stroke-linejoin="round"/>
                    </svg>
                    Centre WhatsApp
                </a>
            </div>

            <div class="fk-nav-section">
                <p class="fk-nav-section-label">Finance & Analyse</p>

                <a href="/admin/payments.php" class="fk-nav-item<?= fk_nav_active('/admin/payments.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="2" y="5" width="20" height="14" rx="2.5" stroke="currentColor" fill="none"/>
                        <path d="M2 10h20" stroke="currentColor" stroke-linecap="round"/>
                        <path d="M6 15h4" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
                    </svg>
                    Paiements
                </a>

                <a href="/admin/reports.php" class="fk-nav-item<?= fk_nav_active('/admin/reports.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 20h18M8 20V10M12 20V4M16 20v-7" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Rapports
                </a>

                <a href="/admin/qr.php" class="fk-nav-item<?= fk_nav_active('/admin/qr.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" fill="none"/>
                        <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" fill="none"/>
                        <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" fill="none"/>
                        <path d="M14 14h2v2h-2zM18 14h3M14 18v3M18 18h3M18 21h3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    QR / NFC
                </a>
            </div>

            <div class="fk-nav-section">
                <p class="fk-nav-section-label">Système</p>

                <a href="/admin/settings.php" class="fk-nav-item<?= fk_nav_active('/admin/settings.php', $current_path) ?>">
                    <svg class="fk-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="3" stroke="currentColor" fill="none"/>
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" fill="none"/>
                    </svg>
                    Paramètres
                </a>
            </div>

        </nav>

        <div class="fk-sidebar-footer">
            <div class="fk-sidebar-user">
                <div class="fk-sidebar-avatar"><?= htmlspecialchars($admin_initial, ENT_QUOTES) ?></div>
                <div class="fk-sidebar-user-info">
                    <div class="fk-sidebar-user-name" title="<?= htmlspecialchars($admin_name, ENT_QUOTES) ?>"><?= htmlspecialchars($admin_name, ENT_QUOTES) ?></div>
                    <a href="/admin/logout.php" class="fk-sidebar-logout">Déconnexion</a>
                </div>
            </div>
        </div>

    </aside>

    <div class="fk-main">

        <header class="fk-topbar">
            <div class="fk-topbar-left">
                <button class="fk-hamburger" id="fkHamburger" onclick="fkOpenSidebar()" aria-label="Ouvrir le menu">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                    </svg>
                </button>
                <nav class="fk-breadcrumb" aria-label="Fil d'ariane">
                    <a href="/admin" style="text-decoration:none;color:inherit;">Faiza Kids</a>
                    <span class="fk-breadcrumb-sep">›</span>
                    <span class="fk-breadcrumb-current"><?= htmlspecialchars($page_title, ENT_QUOTES) ?></span>
                </nav>
            </div>

            <div class="fk-topbar-right">
                <div class="fk-search-bar" role="search">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.35-4.35" stroke-linecap="round"/>
                    </svg>
                    <input type="search" placeholder="Rechercher une réservation, client…" aria-label="Recherche globale">
                </div>

                <button class="fk-notif-btn" id="fkNotifBtn" aria-label="Notifications" onclick="fkToggleNotifications()">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 01-3.46 0" stroke-linecap="round"/>
                    </svg>
                    <?php if ($notif_count > 0): ?>
                    <span class="fk-notif-badge" id="fkNotifCount"><?= $notif_count > 99 ? '99+' : $notif_count ?></span>
                    <?php endif; ?>
                </button>

                <div class="fk-avatar-wrap" id="fkAvatarWrap">
                    <button class="fk-avatar-btn" onclick="fkToggleAvatarDropdown()" aria-haspopup="true" aria-expanded="false" aria-controls="fkAvatarDropdown">
                        <div class="fk-topbar-avatar"><?= htmlspecialchars($admin_initial, ENT_QUOTES) ?></div>
                        <span class="fk-avatar-name"><?= htmlspecialchars($admin_name, ENT_QUOTES) ?></span>
                        <svg class="fk-avatar-caret" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="fk-avatar-dropdown" id="fkAvatarDropdown" role="menu">
                        <div class="fk-avatar-dropdown-header">
                            <strong><?= htmlspecialchars($admin_name, ENT_QUOTES) ?></strong>
                            <span><?= htmlspecialchars($admin_email, ENT_QUOTES) ?></span>
                        </div>
                        <a href="/admin/profile.php" class="fk-dd-item" role="menuitem">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke-linecap="round"/></svg>
                            Mon profil
                        </a>
                        <a href="/admin/settings.php" class="fk-dd-item" role="menuitem">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06A1.65 1.65 0 0015 19.4a1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                            Paramètres
                        </a>
                        <hr class="fk-dd-divider">
                        <a href="/admin/logout.php" class="fk-dd-item fk-dd-danger" role="menuitem">
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="fk-content">
