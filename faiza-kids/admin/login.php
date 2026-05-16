<?php
/**
 * Faiza Kids Concierge — Page de connexion
 * Standalone page (does not use layout-top/bottom)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Already authenticated → redirect
if (is_logged_in()) {
    header('Location: /admin');
    exit;
}

$error    = '';
$username = '';

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Veuillez saisir votre identifiant et votre mot de passe.";
    } elseif (login($username, $password)) {
        $redirect = $_GET['redirect'] ?? '/admin';
        // Validate redirect is local
        $redirect = filter_var($redirect, FILTER_SANITIZE_URL);
        if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            $redirect = '/admin';
        }
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = "Identifiants incorrects. Veuillez réessayer.";
        // Slight delay to deter brute force
        usleep(400000);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion | Faiza Kids Concierge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/app.css">

    <style>
        :root {
            --fk-dark:     #0D2B1D;
            --fk-mid:      #2D6A4F;
            --fk-btn:      #52B788;
            --fk-gold:     #E8C342;
            --fk-border:   #E5EDE9;
            --fk-text:     #1A2E24;
            --fk-muted:    #6B7A72;
            --fk-light:    #F8FAF9;
            --fk-radius:   14px;
            --fk-shadow:   0 4px 24px rgba(0,0,0,.12);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 15px; height: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--fk-light);
            color: var(--fk-text);
            min-height: 100vh;
            display: flex;
        }

        /* ─── Split layout ───────────────────────────────────────── */
        .fk-login-shell {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* LEFT HERO */
        .fk-login-hero {
            flex: 1.1;
            background: var(--fk-dark);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 52px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative pattern */
        .fk-login-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(82,183,136,.18) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(232,195,66,.10) 0%, transparent 40%),
                radial-gradient(circle at 60% 10%, rgba(45,106,79,.3) 0%, transparent 35%);
            pointer-events: none;
        }
        .fk-hero-pattern {
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2352B788' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
            opacity: .6;
        }

        .fk-hero-top { position: relative; z-index: 1; }
        .fk-hero-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 64px;
        }
        .fk-hero-logo-circle {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #52B788, #2D6A4F);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; font-weight: 800; color: #fff;
            box-shadow: 0 4px 16px rgba(82,183,136,.5);
        }
        .fk-hero-logo-text strong {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: #FFFFFF;
        }
        .fk-hero-logo-text span {
            font-size: .72rem;
            color: var(--fk-gold);
            font-weight: 500;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .fk-hero-headline {
            font-size: 2rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -.03em;
            line-height: 1.2;
            margin-bottom: 18px;
        }
        .fk-hero-headline em {
            font-style: normal;
            color: var(--fk-gold);
        }
        .fk-hero-tagline {
            font-size: .9rem;
            color: rgba(255,255,255,.65);
            line-height: 1.65;
            max-width: 380px;
        }

        /* Feature pills */
        .fk-hero-features {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 44px;
            position: relative; z-index: 1;
        }
        .fk-feature-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px;
            backdrop-filter: blur(6px);
        }
        .fk-feature-icon {
            width: 36px; height: 36px;
            background: rgba(82,183,136,.15);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            color: #52B788;
            flex-shrink: 0;
        }
        .fk-feature-text strong {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: #FFFFFF;
            margin-bottom: 1px;
        }
        .fk-feature-text span {
            font-size: .7rem;
            color: rgba(255,255,255,.5);
        }

        .fk-hero-bottom {
            position: relative; z-index: 1;
            font-size: .7rem;
            color: rgba(255,255,255,.3);
        }

        /* RIGHT FORM */
        .fk-login-form-side {
            flex: .9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            background: var(--fk-light);
        }

        .fk-login-card {
            background: #FFFFFF;
            border: 1px solid var(--fk-border);
            border-radius: 20px;
            padding: 42px 44px;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--fk-shadow);
        }

        .fk-login-card-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }
        .fk-login-card-logo-circle {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #52B788, #2D6A4F);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; font-weight: 800; color: #fff;
        }
        .fk-login-card-logo span {
            font-size: .88rem;
            font-weight: 700;
            color: var(--fk-text);
        }

        .fk-login-heading {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--fk-text);
            letter-spacing: -.02em;
            margin-bottom: 6px;
        }
        .fk-login-subheading {
            font-size: .8rem;
            color: var(--fk-muted);
            margin-bottom: 28px;
        }

        /* Error box */
        .fk-error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-left: 4px solid #EF4444;
            border-radius: 9px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: .8rem;
            color: #991B1B;
            line-height: 1.45;
        }
        .fk-error-box svg { flex-shrink: 0; margin-top: 1px; }

        /* Form elements */
        .fk-form-group { margin-bottom: 20px; }
        .fk-form-label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            color: var(--fk-text);
            margin-bottom: 7px;
        }
        .fk-input-wrap {
            position: relative;
        }
        .fk-input-icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--fk-muted);
            pointer-events: none;
            display: flex;
        }
        .fk-input {
            display: block;
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1px solid var(--fk-border);
            border-radius: 10px;
            font-size: .84rem;
            font-family: 'Inter', sans-serif;
            color: var(--fk-text);
            background: #FFFFFF;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
        }
        .fk-input:focus {
            border-color: #52B788;
            box-shadow: 0 0 0 3px rgba(82,183,136,.14);
        }
        .fk-input.fk-input-error {
            border-color: #EF4444;
        }
        .fk-input.fk-input-error:focus {
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
        }
        .fk-input-toggle {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--fk-muted);
            display: flex;
            padding: 4px;
            border-radius: 5px;
            transition: color .14s;
        }
        .fk-input-toggle:hover { color: var(--fk-text); }
        .fk-input-password { padding-right: 44px; }

        /* Submit button */
        .fk-btn-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 20px;
            background: linear-gradient(135deg, #52B788, #2D6A4F);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: opacity .18s, transform .14s, box-shadow .18s;
            box-shadow: 0 4px 14px rgba(82,183,136,.4);
            margin-top: 8px;
        }
        .fk-btn-login:hover { opacity: .9; box-shadow: 0 6px 20px rgba(82,183,136,.5); }
        .fk-btn-login:active { transform: translateY(1px); }
        .fk-btn-login:disabled { opacity: .65; cursor: not-allowed; transform: none; }

        /* Footer note */
        .fk-login-footer {
            margin-top: 24px;
            text-align: center;
            font-size: .72rem;
            color: var(--fk-muted);
        }
        .fk-login-footer a {
            color: var(--fk-btn);
            text-decoration: none;
            font-weight: 500;
        }
        .fk-login-footer a:hover { text-decoration: underline; }

        /* ─── Responsive ──────────────────────────────────────── */
        @media (max-width: 860px) {
            .fk-login-hero { display: none; }
            .fk-login-form-side {
                flex: 1;
                background: var(--fk-dark);
                padding: 24px 18px;
            }
            .fk-login-card {
                border-color: rgba(255,255,255,.1);
                box-shadow: 0 8px 32px rgba(0,0,0,.3);
            }
        }
        @media (max-width: 480px) {
            .fk-login-card { padding: 30px 24px; }
        }
    </style>
</head>
<body>

<div class="fk-login-shell">

    <!-- ══════════════ LEFT HERO ══════════════ -->
    <div class="fk-login-hero" aria-hidden="true">
        <div class="fk-hero-pattern"></div>

        <div class="fk-hero-top">
            <div class="fk-hero-logo">
                <div class="fk-hero-logo-circle">F</div>
                <div class="fk-hero-logo-text">
                    <strong>Faiza Kids</strong>
                    <span>Concierge</span>
                </div>
            </div>

            <h1 class="fk-hero-headline">
                Gestion premium<br>du <em>baby-sitting</em><br>hôtelier au Maroc
            </h1>
            <p class="fk-hero-tagline">
                Service de baby-sitting premium pour hôtels de luxe — Gestion centralisée des réservations, babysitters et hôtels partenaires.
            </p>

            <div class="fk-hero-features">
                <div class="fk-feature-pill">
                    <div class="fk-feature-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 21V8l9-5 9 5v13H3z" fill="none" stroke-linejoin="round"/>
                            <path d="M9 21v-6h6v6"/>
                        </svg>
                    </div>
                    <div class="fk-feature-text">
                        <strong>Hôtels de luxe partenaires</strong>
                        <span>Intégration directe avec la conciergerie</span>
                    </div>
                </div>
                <div class="fk-feature-pill">
                    <div class="fk-feature-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M21 11.5C21 16.19 16.97 20 12 20a9.25 9.25 0 01-4.255-1.03L3 20l1.073-4.596A8.748 8.748 0 013 11.5C3 6.81 7.03 3 12 3s9 3.81 9 8.5z" fill="none" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="fk-feature-text">
                        <strong>Centre WhatsApp intégré</strong>
                        <span>Communication instantanée avec les familles</span>
                    </div>
                </div>
                <div class="fk-feature-pill">
                    <div class="fk-feature-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 17l6-6 4 4 8-8M21 7h-4V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="fk-feature-text">
                        <strong>Tableau de bord analytique</strong>
                        <span>Revenus, taux d'occupation, performances</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="fk-hero-bottom">
            &copy; <?= date('Y') ?> Faiza Kids Concierge · Maroc
        </div>
    </div>
    <!-- END HERO -->

    <!-- ══════════════ RIGHT FORM ══════════════ -->
    <div class="fk-login-form-side">
        <div class="fk-login-card">

            <!-- Card logo -->
            <div class="fk-login-card-logo">
                <div class="fk-login-card-logo-circle">F</div>
                <span>Faiza Kids Concierge</span>
            </div>

            <h2 class="fk-login-heading">Connexion</h2>
            <p class="fk-login-subheading">Accédez à votre espace d'administration.</p>

            <!-- Error message -->
            <?php if ($error): ?>
            <div class="fk-error-box" role="alert">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2.2" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01" stroke-linecap="round"/>
                </svg>
                <?= htmlspecialchars($error, ENT_QUOTES) ?>
            </div>
            <?php endif; ?>

            <!-- Login form -->
            <form method="POST" action="" novalidate id="loginForm" autocomplete="on">

                <!-- Username -->
                <div class="fk-form-group">
                    <label class="fk-form-label" for="username">Identifiant</label>
                    <div class="fk-input-wrap">
                        <span class="fk-input-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.582-7 8-7s8 3 8 7" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="fk-input<?= $error ? ' fk-input-error' : '' ?>"
                            placeholder="Nom d'utilisateur"
                            value="<?= htmlspecialchars($username, ENT_QUOTES) ?>"
                            autocomplete="username"
                            required
                            autofocus
                            spellcheck="false"
                            autocapitalize="off"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="fk-form-group">
                    <label class="fk-form-label" for="password">Mot de passe</label>
                    <div class="fk-input-wrap">
                        <span class="fk-input-icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="5" y="11" width="14" height="10" rx="2" fill="none"/>
                                <path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="fk-input fk-input-password<?= $error ? ' fk-input-error' : '' ?>"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="fk-input-toggle" id="togglePassword" aria-label="Afficher/masquer le mot de passe" title="Afficher le mot de passe">
                            <!-- Eye icon (shown by default) -->
                            <svg id="iconEye" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                            <!-- Eye-off icon (hidden by default) -->
                            <svg id="iconEyeOff" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display:none;" aria-hidden="true">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19M1 1l22 22" stroke-linecap="round"/>
                                <path d="M10.73 10.73A2 2 0 0013.27 13.27" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="fk-btn-login" id="loginBtn">
                    <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M11 16l4-4-4-4M3 12h12M15 4h4a1 1 0 011 1v14a1 1 0 01-1 1h-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Se connecter
                </button>

            </form>

            <p class="fk-login-footer">
                Besoin d'aide ? Contactez le
                <a href="mailto:support@faizakids.ma">support technique</a>.
            </p>

        </div><!-- /.fk-login-card -->
    </div>
    <!-- END FORM SIDE -->

</div><!-- /.fk-login-shell -->

<script>
(function () {
    'use strict';

    // Show/hide password toggle
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput  = document.getElementById('password');
    const eyeIcon   = document.getElementById('iconEye');
    const eyeOff    = document.getElementById('iconEyeOff');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            const isHidden = pwdInput.type === 'password';
            pwdInput.type = isHidden ? 'text' : 'password';
            eyeIcon.style.display  = isHidden ? 'none'  : '';
            eyeOff.style.display   = isHidden ? ''      : 'none';
            toggleBtn.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    }

    // Loading state on submit
    const form    = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    if (form && loginBtn) {
        form.addEventListener('submit', function () {
            loginBtn.disabled = true;
            loginBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:fkSpin .7s linear infinite;" aria-hidden="true">
                    <path d="M4 4v5h.582m0 0A8.001 8.001 0 0120 12M4.582 9H9" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Connexion…`;
        });
    }
})();
</script>

<style>
@keyframes fkSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

</body>
</html>
