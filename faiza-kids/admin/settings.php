<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page_title    = 'Paramètres';
$page_subtitle = 'Configuration de l\'application et des services.';

$all_settings = db_fetch_all("SELECT setting_key, setting_value FROM settings", []);
$s = [];
foreach ($all_settings as $row) { $s[$row['setting_key']] = $row['setting_value']; }

$hotels = db_fetch_all("SELECT id, name, day_rate, night_rate FROM hotels WHERE is_active=1 ORDER BY sort_order", []);
$active_tab = $_GET['tab'] ?? 'company';

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;margin-bottom:20px;border-bottom:2px solid #E5EDE9;overflow-x:auto">
  <?php $tabs = [
    'company'  =>'🏢 Société',
    'booking'  =>'📅 Réservation',
    'hotels'   =>'🏨 Tarifs hôtels',
    'payment'  =>'💳 Paiement',
    'smtp'     =>'📧 SMTP',
    'whatsapp' =>'💬 WhatsApp',
    'pdf'      =>'📄 PDF',
    'backup'   =>'💾 Sauvegarde',
    'maintenance'=>'🔧 Maintenance',
  ];
  foreach ($tabs as $tid => $tlbl): ?>
  <a href="?tab=<?= $tid ?>" style="padding:12px 16px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;border-bottom:2px solid <?= $active_tab===$tid?'#2D6A4F':'transparent' ?>;color:<?= $active_tab===$tid?'#2D6A4F':'#6B7A72' ?>;margin-bottom:-2px">
    <?= $tlbl ?>
  </a>
  <?php endforeach; ?>
</div>

<div id="settingsMsg" style="display:none;margin-bottom:16px" class="fk-alert"></div>

<?php if ($active_tab === 'company'): ?>
<div class="fk-card" style="max-width:680px">
  <div class="fk-card-header"><h3 class="fk-section-title">Informations société</h3></div>
  <form onsubmit="saveSettings(event,'company')" id="form-company" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div style="grid-column:span 2"><label class="fk-label">Nom du service</label>
      <input type="text" name="app_name" class="fk-input" value="<?= sanitize($s['app_name']??'Faiza Kids Concierge') ?>"></div>
    <div><label class="fk-label">Nom société</label>
      <input type="text" name="company_name" class="fk-input" value="<?= sanitize($s['company_name']??'Faiza Multiservice') ?>"></div>
    <div><label class="fk-label">Email société</label>
      <input type="email" name="company_email" class="fk-input" value="<?= sanitize($s['company_email']??'') ?>"></div>
    <div><label class="fk-label">Téléphone</label>
      <input type="text" name="company_phone" class="fk-input" value="<?= sanitize($s['company_phone']??'') ?>"></div>
    <div><label class="fk-label">WhatsApp admin</label>
      <input type="text" name="company_whatsapp" class="fk-input" value="<?= sanitize($s['company_whatsapp']??'') ?>"></div>
    <div><label class="fk-label">Ville</label>
      <input type="text" name="company_city" class="fk-input" value="<?= sanitize($s['company_city']??'Agadir') ?>"></div>
    <div><label class="fk-label">Adresse</label>
      <input type="text" name="company_address" class="fk-input" value="<?= sanitize($s['company_address']??'') ?>"></div>
    <div style="grid-column:span 2"><label class="fk-label">Logo Faiza (upload)</label>
      <div style="display:flex;align-items:center;gap:12px">
        <?php $logo = $s['logo_path']??''; ?>
        <?php if ($logo): ?><img src="<?= defined('BASE_URL')?BASE_URL.'/uploads/logos/'.basename($logo):$logo ?>" style="height:40px;border-radius:6px;border:1px solid #E5EDE9"><?php endif; ?>
        <button type="button" onclick="document.getElementById('logoFileInput').click()" class="fk-btn fk-btn-secondary fk-btn-sm">Choisir un logo</button>
        <input type="file" id="logoFileInput" accept=".png,.jpg,.jpeg,.svg,.webp" style="display:none" onchange="uploadLogo(this,'company_logo','logo_path')">
        <input type="hidden" name="logo_path" id="logo_path_val" value="<?= sanitize($logo) ?>">
      </div>
    </div>
    <div style="grid-column:span 2"><button type="submit" class="fk-btn fk-btn-primary">Enregistrer</button></div>
  </form>
</div>

<?php elseif ($active_tab === 'booking'): ?>
<div class="fk-card" style="max-width:480px">
  <div class="fk-card-header"><h3 class="fk-section-title">Règles de réservation</h3></div>
  <form onsubmit="saveSettings(event,'booking')" id="form-booking" style="display:flex;flex-direction:column;gap:14px">
    <div><label class="fk-label">Délai minimum avant réservation (heures)</label>
      <input type="number" name="min_booking_hours" class="fk-input" value="<?= (int)($s['min_booking_hours']??24) ?>" min="1"></div>
    <div><label class="fk-label">Délai minimum annulation (heures)</label>
      <input type="number" name="cancellation_hours" class="fk-input" value="<?= (int)($s['cancellation_hours']??3) ?>" min="1"></div>
    <div><label class="fk-label">Remboursement si annulation tardive (%)</label>
      <input type="number" name="late_cancel_refund" class="fk-input" value="<?= (int)($s['late_cancel_refund']??50) ?>" min="0" max="100"></div>
    <div><label class="fk-label">Langue par défaut</label>
      <select name="default_language" class="fk-select">
        <option value="fr" <?= ($s['default_language']??'fr')==='fr'?'selected':'' ?>>Français</option>
        <option value="en" <?= ($s['default_language']??'')==='en'?'selected':'' ?>>English</option>
        <option value="ar" <?= ($s['default_language']??'')==='ar'?'selected':'' ?>>العربية</option>
      </select></div>
    <div><label class="fk-label">Devise</label>
      <select name="currency" class="fk-select">
        <option value="DH" <?= ($s['currency']??'DH')==='DH'?'selected':'' ?>>DH (Dirham marocain)</option>
        <option value="MAD" <?= ($s['currency']??'')==='MAD'?'selected':'' ?>>MAD</option>
        <option value="EUR" <?= ($s['currency']??'')==='EUR'?'selected':'' ?>>EUR</option>
      </select></div>
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
      <input type="checkbox" name="city_enabled" value="1" <?= ($s['city_enabled']??'1')==='1'?'checked':'' ?> style="accent-color:#2D6A4F;width:18px;height:18px">
      <span class="fk-label" style="margin:0">Activer les réservations City / Clients externes</span>
    </label>
    <button type="submit" class="fk-btn fk-btn-primary" style="align-self:flex-start">Enregistrer</button>
  </form>
</div>

<?php elseif ($active_tab === 'hotels'): ?>
<div class="fk-card" style="max-width:680px">
  <div class="fk-card-header"><h3 class="fk-section-title">Tarifs internes par hôtel</h3></div>
  <div class="fk-alert fk-alert-info" style="margin-bottom:16px;font-size:13px">Ces tarifs sont utilisés pour les estimations internes uniquement. Ils ne sont pas affichés aux clients.</div>
  <?php foreach ($hotels as $h): ?>
  <div style="border:1px solid #E5EDE9;border-radius:12px;padding:16px;margin-bottom:12px">
    <div style="font-weight:600;color:#1A2E24;margin-bottom:12px"><?= sanitize($h['name']) ?></div>
    <form onsubmit="saveHotelRate(event,<?= $h['id'] ?>)" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <div><label class="fk-label">Tarif jour (DH)</label>
        <input type="number" name="day_rate" class="fk-input" style="width:120px" value="<?= (float)$h['day_rate'] ?>" min="0" step="10"></div>
      <div><label class="fk-label">Tarif nuit (DH)</label>
        <input type="number" name="night_rate" class="fk-input" style="width:120px" value="<?= (float)$h['night_rate'] ?>" min="0" step="10"></div>
      <button type="submit" class="fk-btn fk-btn-primary fk-btn-sm">Sauvegarder</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>

<?php elseif ($active_tab === 'payment'): ?>
<div class="fk-card" style="max-width:580px">
  <div class="fk-card-header"><h3 class="fk-section-title">Informations bancaires</h3></div>
  <form onsubmit="saveSettings(event,'payment')" id="form-payment" style="display:flex;flex-direction:column;gap:14px">
    <div><label class="fk-label">Nom de la banque</label>
      <input type="text" name="bank_name" class="fk-input" value="<?= sanitize($s['bank_name']??'CIH Bank') ?>"></div>
    <div><label class="fk-label">Titulaire du compte</label>
      <input type="text" name="bank_account_name" class="fk-input" value="<?= sanitize($s['bank_account_name']??'Faiza Multiservice') ?>"></div>
    <div><label class="fk-label">RIB / IBAN</label>
      <input type="text" name="bank_rib" class="fk-input" value="<?= sanitize($s['bank_rib']??'') ?>" placeholder="230 000 0000000000000000 00"></div>
    <div><label class="fk-label">Instructions de paiement</label>
      <textarea name="payment_instructions" class="fk-input" rows="3"><?= sanitize($s['payment_instructions']??'') ?></textarea></div>
    <div><label class="fk-label">Message client (paiement)</label>
      <textarea name="payment_client_message" class="fk-input" rows="3"><?= sanitize($s['payment_client_message']??'') ?></textarea></div>
    <button type="submit" class="fk-btn fk-btn-primary" style="align-self:flex-start">Enregistrer</button>
  </form>
</div>

<?php elseif ($active_tab === 'smtp'): ?>
<div class="fk-card" style="max-width:580px">
  <div class="fk-card-header"><h3 class="fk-section-title">Configuration SMTP</h3></div>
  <form onsubmit="saveSettings(event,'smtp')" id="form-smtp" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
    <div style="grid-column:span 2"><label class="fk-label">Hôte SMTP</label>
      <input type="text" name="smtp_host" class="fk-input" value="<?= sanitize($s['smtp_host']??'') ?>" placeholder="smtp.gmail.com"></div>
    <div><label class="fk-label">Port</label>
      <input type="number" name="smtp_port" class="fk-input" value="<?= sanitize($s['smtp_port']??'587') ?>"></div>
    <div><label class="fk-label">Chiffrement</label>
      <select name="smtp_encryption" class="fk-select">
        <option value="tls" <?= ($s['smtp_encryption']??'tls')==='tls'?'selected':'' ?>>TLS</option>
        <option value="ssl" <?= ($s['smtp_encryption']??'')==='ssl'?'selected':'' ?>>SSL</option>
        <option value="none" <?= ($s['smtp_encryption']??'')==='none'?'selected':'' ?>>Aucun</option>
      </select></div>
    <div><label class="fk-label">Nom d'expéditeur</label>
      <input type="text" name="smtp_from_name" class="fk-input" value="<?= sanitize($s['smtp_from_name']??'Faiza Kids Concierge') ?>"></div>
    <div><label class="fk-label">Email expéditeur</label>
      <input type="email" name="smtp_from_email" class="fk-input" value="<?= sanitize($s['smtp_from_email']??'') ?>"></div>
    <div><label class="fk-label">Nom d'utilisateur SMTP</label>
      <input type="text" name="smtp_user" class="fk-input" value="<?= sanitize($s['smtp_user']??'') ?>"></div>
    <div><label class="fk-label">Mot de passe SMTP</label>
      <input type="password" name="smtp_pass" class="fk-input" value="<?= sanitize($s['smtp_pass']??'') ?>"></div>
    <div style="grid-column:span 2;display:flex;gap:10px;align-items:center">
      <button type="submit" class="fk-btn fk-btn-primary">Enregistrer</button>
      <button type="button" onclick="testSMTP()" class="fk-btn fk-btn-secondary">🔌 Tester la connexion</button>
      <div id="smtpTestResult" style="font-size:13px;color:#6B7A72"></div>
    </div>
  </form>
</div>

<?php elseif ($active_tab === 'backup'): ?>
<div class="fk-card" style="max-width:480px">
  <div class="fk-card-header"><h3 class="fk-section-title">Sauvegarde des données</h3></div>
  <div style="display:flex;flex-direction:column;gap:12px">
    <a href="/api/settings?action=export_backup" class="fk-btn fk-btn-secondary" style="display:block;text-align:center">💾 Sauvegarde complète (JSON)</a>
    <a href="/api/export?type=bookings" class="fk-btn fk-btn-secondary" style="display:block;text-align:center">⬇ Exporter réservations (CSV)</a>
    <a href="/api/export?type=payments" class="fk-btn fk-btn-secondary" style="display:block;text-align:center">⬇ Exporter paiements (CSV)</a>
  </div>
</div>

<?php elseif ($active_tab === 'maintenance'): ?>
<div class="fk-card" style="max-width:520px;border:2px solid #FEE2E2">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <div style="width:40px;height:40px;border-radius:10px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;font-size:20px">⚠️</div>
    <div>
      <div style="font-size:15px;font-weight:700;color:#991B1B">Zone dangereuse – Maintenance</div>
      <div style="font-size:13px;color:#6B7A72;margin-top:2px">Actions irréversibles</div>
    </div>
  </div>
  <div class="fk-alert fk-alert-warning" style="margin-bottom:16px;font-size:13px">
    Cette action supprimera <strong>définitivement</strong> toutes les réservations, clients et données de test.<br>
    La configuration, les hôtels, les tarifs, les templates WhatsApp et le compte admin seront conservés.
  </div>
  <form onsubmit="resetData(event)" style="display:flex;flex-direction:column;gap:12px">
    <div><label class="fk-label">Code de confirmation (tapez exactement : <code>RESET</code>)</label>
      <input type="text" id="resetCode" class="fk-input" placeholder="RESET" style="font-family:monospace"></div>
    <button type="submit" class="fk-btn fk-btn-danger" style="align-self:flex-start">🗑️ Réinitialiser les données de test</button>
    <div id="resetMsg" style="display:none" class="fk-alert"></div>
  </form>
</div>
<?php endif; ?>

<script>
async function saveSettings(e, section) {
  e.preventDefault();
  const form = document.getElementById('form-'+section);
  if (!form) return;
  const data = new FormData(form);
  const settings = {};
  data.forEach((v,k) => settings[k] = v);
  // Handle unchecked checkboxes
  form.querySelectorAll('input[type=checkbox]').forEach(cb=>{
    if(!cb.checked) settings[cb.name]='0';
    else settings[cb.name]='1';
  });
  const res = await fetch('/api/settings?action=save', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({settings})});
  const json = await res.json();
  showSettingsMsg(json.success ? 'Paramètres enregistrés avec succès.' : (json.error||'Erreur.'), json.success?'success':'error');
}

async function saveHotelRate(e, hotelId) {
  e.preventDefault();
  const form = e.target;
  const day_rate   = form.querySelector('[name=day_rate]').value;
  const night_rate = form.querySelector('[name=night_rate]').value;
  const res = await fetch('/api/hotels?action=update&id='+hotelId, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({day_rate,night_rate})});
  const json = await res.json();
  showSettingsMsg(json.success ? 'Tarifs mis à jour.' : (json.error||'Erreur.'), json.success?'success':'error');
}

async function testSMTP() {
  const res = await fetch('/api/settings?action=test_smtp', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({})});
  const json = await res.json();
  document.getElementById('smtpTestResult').textContent = json.success ? '✅ Connexion réussie !' : ('❌ '+(json.error||'Échec'));
  document.getElementById('smtpTestResult').style.color = json.success ? '#22C55E' : '#EF4444';
}

async function resetData(e) {
  e.preventDefault();
  const code = document.getElementById('resetCode').value.trim();
  if (code !== 'RESET') { showSettingsMsg('Code incorrect. Tapez exactement RESET.','error'); return; }
  if (!confirm('Êtes-vous certain de vouloir supprimer toutes les données de test ? Cette action est irréversible.')) return;
  const res = await fetch('/api/settings?action=reset_test_data', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({confirmation:'RESET_TEST_DATA',force:true})});
  const json = await res.json();
  const el = document.getElementById('resetMsg');
  el.textContent = json.success ? 'Application réinitialisée avec succès. Prête pour livraison.' : (json.error||'Erreur.');
  el.className = 'fk-alert fk-alert-'+(json.success?'success':'error');
  el.style.display = 'block';
}

function showSettingsMsg(msg, type='success') {
  const el = document.getElementById('settingsMsg');
  el.textContent = msg;
  el.className = 'fk-alert fk-alert-'+type;
  el.style.display = 'block';
  window.scrollTo({top:0,behavior:'smooth'});
  setTimeout(()=>el.style.display='none', 4000);
}

async function uploadLogo(input, type, fieldId) {
  if (!input.files[0]) return;
  const fd = new FormData();
  fd.append('file', input.files[0]);
  const res = await fetch('/api/upload?action=logo', {method:'POST',body:fd});
  const json = await res.json();
  if (json.success) {
    document.getElementById(fieldId+'_val').value = json.filename;
    showSettingsMsg('Logo uploadé avec succès.');
  } else showSettingsMsg(json.error||'Erreur upload.','error');
}
</script>

<?php include 'layout-bottom.php'; ?>
