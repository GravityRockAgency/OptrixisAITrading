<!DOCTYPE html>
<?php
/**
 * Faiza Kids Concierge – Installer
 * Run once, then DELETE this file for security.
 */
define('FK_ROOT', __DIR__);

$step     = $_GET['step'] ?? '1';
$error    = '';
$success  = '';
$messages = [];

// ── Step 2: Process form ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    $db_host    = trim($_POST['db_host']    ?? 'localhost');
    $db_name    = trim($_POST['db_name']    ?? '');
    $db_user    = trim($_POST['db_user']    ?? '');
    $db_pass    = $_POST['db_pass']         ?? '';
    $base_url   = rtrim(trim($_POST['base_url'] ?? ''), '/');
    $app_secret = bin2hex(random_bytes(24));

    if (!$db_name || !$db_user) {
        $error = 'Le nom de la base de données et l\'utilisateur sont obligatoires.';
    } else {
        try {
            // Test connection
            $dsn = "mysql:host=$db_host;charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");

            // Define constants for migration
            define('DB_HOST',        $db_host);
            define('DB_NAME',        $db_name);
            define('DB_USER',        $db_user);
            define('DB_PASS',        $db_pass);
            define('BASE_URL',       $base_url);
            define('APP_SECRET',     $app_secret);
            define('APP_ENV',        'production');
            define('APP_VERSION',    '2.0.0');
            define('UPLOAD_MAX_SIZE', 8388608);
            define('UPLOAD_PATH',    FK_ROOT . '/uploads');
            define('UPLOAD_URL',     $base_url . '/uploads');

            // Run migrations
            require_once FK_ROOT . '/includes/migrate.php';
            $messages = run_migrations($pdo);

            // Create uploads directories
            $dirs = ['uploads','uploads/logos','uploads/hotels','uploads/proofs','uploads/pdf'];
            foreach ($dirs as $d) {
                if (!is_dir(FK_ROOT . '/' . $d)) {
                    mkdir(FK_ROOT . '/' . $d, 0755, true);
                }
            }

            // Create .htaccess for uploads directory
            file_put_contents(FK_ROOT . '/uploads/.htaccess', "Options -Indexes\n<FilesMatch \"\.(php|php5|phtml|pl|py|cgi|sh)$\">\n    Order deny,allow\n    Deny from all\n</FilesMatch>\n");

            // Write config.php
            $config_content = "<?php\n"
                . "define('DB_HOST',        '$db_host');\n"
                . "define('DB_NAME',        '$db_name');\n"
                . "define('DB_USER',        '$db_user');\n"
                . "define('DB_PASS',        " . var_export($db_pass, true) . ");\n"
                . "define('BASE_URL',       '$base_url');\n"
                . "define('APP_SECRET',     '$app_secret');\n"
                . "define('APP_ENV',        'production');\n"
                . "define('APP_VERSION',    '2.0.0');\n"
                . "define('UPLOAD_MAX_SIZE', 8388608);\n"
                . "define('UPLOAD_PATH',    __DIR__ . '/uploads');\n"
                . "define('UPLOAD_URL',     BASE_URL . '/uploads');\n";

            if (file_put_contents(FK_ROOT . '/config.php', $config_content) === false) {
                $error = 'Impossible d\'écrire config.php. Vérifiez les permissions d\'écriture.';
            } else {
                $success = 'Installation réussie !';
                $step    = '3';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion MySQL : ' . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Pre-flight checks ─────────────────────────────────────────────────────────
$checks = [
    'php_version' => [
        'label' => 'PHP 8.0+',
        'ok'    => version_compare(PHP_VERSION, '8.0.0', '>='),
        'value' => PHP_VERSION,
    ],
    'pdo_mysql' => [
        'label' => 'Extension PDO MySQL',
        'ok'    => extension_loaded('pdo_mysql'),
        'value' => extension_loaded('pdo_mysql') ? 'Chargée' : 'Manquante',
    ],
    'gd' => [
        'label' => 'Extension GD (images)',
        'ok'    => extension_loaded('gd'),
        'value' => extension_loaded('gd') ? 'Chargée' : 'Manquante',
    ],
    'fileinfo' => [
        'label' => 'Extension FileInfo',
        'ok'    => extension_loaded('fileinfo'),
        'value' => extension_loaded('fileinfo') ? 'Chargée' : 'Manquante',
    ],
    'mbstring' => [
        'label' => 'Extension MBString',
        'ok'    => extension_loaded('mbstring'),
        'value' => extension_loaded('mbstring') ? 'Chargée' : 'Manquante',
    ],
    'writable' => [
        'label' => 'Répertoire racine accessible',
        'ok'    => is_writable(FK_ROOT),
        'value' => is_writable(FK_ROOT) ? 'Accessible' : 'Lecture seule',
    ],
];
$checks_pass = !in_array(false, array_column($checks, 'ok'), true);
?>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation – Faiza Kids Concierge</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#F8FAF9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.wrap{width:100%;max-width:680px}
.logo-area{text-align:center;margin-bottom:32px}
.logo-circle{width:60px;height:60px;border-radius:50%;background:#0D2B1D;color:#52B788;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
.logo-title{font-size:22px;font-weight:700;color:#1A2E24}
.logo-sub{font-size:14px;color:#6B7A72;margin-top:4px}
.card{background:#fff;border-radius:16px;border:1px solid #E5EDE9;padding:32px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.card-title{font-size:18px;font-weight:700;color:#1A2E24;margin-bottom:4px}
.card-sub{font-size:13px;color:#6B7A72;margin-bottom:24px}
.check-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #F0F5F2}
.check-row:last-child{border-bottom:none}
.check-icon{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.check-ok{background:#D1FAE5;color:#059669}
.check-fail{background:#FEE2E2;color:#DC2626}
.check-label{font-size:14px;color:#1A2E24;flex:1}
.check-val{font-size:13px;color:#6B7A72}
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:13px;font-weight:600;color:#1A2E24;margin-bottom:6px}
.form-input{width:100%;padding:10px 14px;border:1px solid #E5EDE9;border-radius:10px;font-size:14px;color:#1A2E24;outline:none;transition:border .2s}
.form-input:focus{border-color:#52B788;box-shadow:0 0 0 3px rgba(82,183,136,.15)}
.form-hint{font-size:12px;color:#6B7A72;margin-top:4px}
.btn-primary{width:100%;padding:13px;background:#2D6A4F;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:background .2s;margin-top:8px}
.btn-primary:hover{background:#40916C}
.btn-primary:disabled{background:#9CA3AF;cursor:not-allowed}
.alert{padding:14px 16px;border-radius:10px;font-size:14px;margin-bottom:20px}
.alert-error{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B}
.alert-success{background:#F0FDF4;border:1px solid #A7F3D0;color:#065F46}
.msg-list{list-style:none;padding:0}
.msg-list li{padding:8px 0;border-bottom:1px solid #F0F5F2;font-size:13px;color:#1A2E24}
.msg-list li:last-child{border-bottom:none}
.msg-ok{color:#059669}
.msg-err{color:#DC2626}
.step-done{text-align:center;padding:16px 0}
.step-done-icon{font-size:48px;margin-bottom:16px}
.step-done h2{font-size:20px;font-weight:700;color:#1A2E24;margin-bottom:8px}
.step-done p{font-size:14px;color:#6B7A72;margin-bottom:24px}
.btn-go{display:inline-block;padding:13px 32px;background:#2D6A4F;color:#fff;border-radius:10px;font-size:15px;font-weight:600;text-decoration:none}
.btn-go:hover{background:#40916C}
.warn{background:#FFFBEB;border:1px solid #FDE68A;color:#92400E;padding:12px 16px;border-radius:10px;font-size:13px;margin-top:20px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:480px){.row2{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo-area">
    <div class="logo-circle">F</div>
    <div class="logo-title">Faiza Kids Concierge</div>
    <div class="logo-sub">Assistant d'installation v2.0</div>
  </div>

<?php if ($step === '3'): ?>
  <!-- ── Step 3: Success ─────────────────────────────────────────────────── -->
  <div class="card">
    <div class="step-done">
      <div class="step-done-icon">✅</div>
      <h2>Installation réussie !</h2>
      <p>L'application Faiza Kids Concierge est prête à l'emploi.</p>
      <a href="/admin/login" class="btn-go">Accéder au tableau de bord →</a>
    </div>
    <hr style="margin:24px 0;border:none;border-top:1px solid #E5EDE9">
    <p style="font-size:13px;font-weight:600;color:#1A2E24;margin-bottom:12px">Résumé de l'installation :</p>
    <ul class="msg-list">
      <?php foreach ($messages as $m): ?>
        <li class="<?= str_starts_with($m,'✓') ? 'msg-ok' : 'msg-err' ?>"><?= htmlspecialchars($m) ?></li>
      <?php endforeach; ?>
    </ul>
    <div class="warn">
      ⚠️ <strong>Important :</strong> Supprimez le fichier <code>install.php</code> de votre serveur pour des raisons de sécurité.
    </div>
  </div>
  <div style="text-align:center;margin-top:16px;font-size:12px;color:#9CA3AF">
    Identifiants admin par défaut : <strong>faizamultiservice</strong> / <strong>Agadir2026@33</strong>
  </div>

<?php elseif ($step === '1'): ?>
  <!-- ── Step 1: Pre-flight ─────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-title">Vérification de l'environnement</div>
    <div class="card-sub">Contrôle des prérequis avant l'installation.</div>

    <?php foreach ($checks as $c): ?>
    <div class="check-row">
      <div class="check-icon <?= $c['ok'] ? 'check-ok' : 'check-fail' ?>"><?= $c['ok'] ? '✓' : '✗' ?></div>
      <div class="check-label"><?= htmlspecialchars($c['label']) ?></div>
      <div class="check-val"><?= htmlspecialchars($c['value']) ?></div>
    </div>
    <?php endforeach; ?>

    <form method="get" style="margin-top:24px">
      <input type="hidden" name="step" value="2">
      <button type="submit" class="btn-primary" <?= $checks_pass ? '' : 'disabled' ?>>
        <?= $checks_pass ? 'Continuer l\'installation →' : 'Corrigez les erreurs ci-dessus' ?>
      </button>
    </form>
  </div>

<?php else: ?>
  <!-- ── Step 2: Configuration ──────────────────────────────────────────── -->
  <div class="card">
    <div class="card-title">Configuration de la base de données</div>
    <div class="card-sub">Entrez les informations de connexion MySQL.</div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="?step=2">
      <div class="row2">
        <div class="form-group">
          <label class="form-label">Hôte MySQL</label>
          <input class="form-input" type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nom de la base de données</label>
          <input class="form-input" type="text" name="db_name" placeholder="faiza_kids" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="row2">
        <div class="form-group">
          <label class="form-label">Utilisateur MySQL</label>
          <input class="form-input" type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Mot de passe MySQL</label>
          <input class="form-input" type="password" name="db_pass" value="">
          <div class="form-hint">Laissez vide si aucun mot de passe.</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">URL de base de l'application</label>
        <input class="form-input" type="url" name="base_url"
          placeholder="https://baby-sitting.faizamultiservice.com"
          value="<?= htmlspecialchars($_POST['base_url'] ?? (isset($_SERVER['HTTP_HOST']) ? 'https://'.$_SERVER['HTTP_HOST'] : '')) ?>">
        <div class="form-hint">Sans slash final. Exemple : https://baby-sitting.faizamultiservice.com</div>
      </div>
      <button type="submit" class="btn-primary">Lancer l'installation ✓</button>
    </form>
  </div>
<?php endif; ?>
</div>
</body>
</html>
