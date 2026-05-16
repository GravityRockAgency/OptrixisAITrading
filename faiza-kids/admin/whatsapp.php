<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page_title    = 'Centre WhatsApp';
$page_subtitle = 'Messages prédéfinis et historique de communication client.';

$templates_raw = db_fetch_all("SELECT * FROM whatsapp_templates ORDER BY sort_order, template_key, language", []);
$templates = [];
foreach ($templates_raw as $t) {
    $templates[$t['template_key']][$t['language']] = $t;
}

$history = db_fetch_all(
    "SELECT wh.*, b.reference as booking_ref FROM whatsapp_history wh
     LEFT JOIN bookings b ON wh.booking_id = b.id
     ORDER BY wh.sent_at DESC LIMIT 100",
    []
);

$template_names_fr = [
    'demande_recue'        => 'Demande reçue',
    'infos_manquantes'     => 'Informations manquantes',
    'tarif_propose'        => 'Tarif proposé',
    'demande_paiement'     => 'Demande de paiement',
    'paiement_recu'        => 'Paiement reçu',
    'reservation_confirmee'=> 'Réservation confirmée',
    'rappel_service'       => 'Rappel avant service',
    'baby_en_route'        => 'Babysitter en route',
    'service_termine'      => 'Service terminé',
    'annulation'           => 'Annulation',
];

$active_tab = $_GET['tab'] ?? 'messages';

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
</div>

<!-- Tab nav -->
<div style="display:flex;gap:0;margin-bottom:20px;border-bottom:2px solid #E5EDE9">
  <?php foreach ([' messages'=>'💬 Messages rapides','history'=>'📋 Historique','settings'=>'⚙️ Paramètres'] as $t=>$l): ?>
  <a href="?tab=<?= trim($t) ?>" style="padding:12px 20px;font-size:14px;font-weight:500;text-decoration:none;border-bottom:2px solid <?= $active_tab===trim($t)?'#2D6A4F':'transparent' ?>;color:<?= $active_tab===trim($t)?'#2D6A4F':'#6B7A72' ?>;margin-bottom:-2px">
    <?= $l ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($active_tab === 'messages'): ?>
<!-- Templates grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px">
<?php foreach ($templates as $key => $langs):
  $name = $template_names_fr[$key] ?? ucfirst(str_replace('_',' ',$key));
  $fr   = $langs['fr'] ?? null;
  $en   = $langs['en'] ?? null;
  $ar   = $langs['ar'] ?? null;
?>
<div class="fk-card">
  <div class="fk-card-header" style="margin-bottom:12px">
    <h3 style="font-size:14px;font-weight:700;color:#1A2E24"><?= sanitize($name) ?></h3>
    <div style="display:flex;gap:4px">
      <span class="fk-badge fk-badge-success fk-badge-sm" style="font-size:10px">FR</span>
      <?php if ($en): ?><span class="fk-badge fk-badge-info fk-badge-sm" style="font-size:10px">EN</span><?php endif; ?>
      <?php if ($ar): ?><span class="fk-badge fk-badge-warning fk-badge-sm" style="font-size:10px">AR</span><?php endif; ?>
    </div>
  </div>
  <div id="preview-<?= $key ?>" style="font-size:12px;color:#6B7A72;background:#F8FAF9;border-radius:8px;padding:10px;max-height:80px;overflow:hidden;line-height:1.6;white-space:pre-wrap;word-break:break-word">
    <?= sanitize(truncate($fr['message'] ?? '—', 180)) ?>
  </div>
  <div style="display:flex;gap:6px;margin-top:12px;flex-wrap:wrap">
    <button onclick="openViewModal(<?= json_encode($key) ?>, <?= json_encode($langs) ?>)" class="fk-btn fk-btn-secondary fk-btn-sm">Voir</button>
    <button onclick="openEditModal(<?= json_encode($key) ?>, <?= json_encode($langs) ?>, <?= json_encode($name) ?>)" class="fk-btn fk-btn-secondary fk-btn-sm">Modifier</button>
    <button onclick="openSendModal(<?= json_encode($key) ?>, <?= json_encode($langs) ?>)" class="fk-btn fk-btn-primary fk-btn-sm">Utiliser</button>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php elseif ($active_tab === 'history'): ?>
<div class="fk-card">
  <div class="fk-card-header">
    <h3 class="fk-section-title">Historique des envois</h3>
    <span class="fk-badge"><?= count($history) ?> entrée(s)</span>
  </div>
  <?php if (empty($history)): ?>
  <div class="fk-empty-state" style="padding:40px">
    <div style="font-size:40px;margin-bottom:12px">💭</div>
    <div class="fk-empty-title">Aucun historique</div>
    <div class="fk-empty-text">Les messages envoyés apparaîtront ici.</div>
  </div>
  <?php else: ?>
  <div class="fk-table-wrapper">
    <table class="fk-table">
      <thead><tr>
        <th>Date</th><th>Client</th><th>Réservation</th><th>Template</th><th>Langue</th><th>Action</th><th>Aperçu</th>
      </tr></thead>
      <tbody>
      <?php foreach ($history as $h): ?>
      <tr>
        <td style="white-space:nowrap;font-size:12px"><?= sanitize($h['sent_at']) ?></td>
        <td>
          <div style="font-weight:500"><?= sanitize($h['client_name'] ?? '—') ?></div>
          <?php if ($h['client_whatsapp']): ?>
          <div style="font-size:11px;color:#25D366"><?= sanitize($h['client_whatsapp']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($h['booking_ref']): ?>
          <a href="/admin/bookings/view/<?= $h['booking_id'] ?>" style="font-family:monospace;color:#2D6A4F"><?= sanitize($h['booking_ref']) ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td style="font-size:13px"><?= sanitize($template_names_fr[$h['template_key']] ?? $h['template_key'] ?? '—') ?></td>
        <td><span class="fk-badge"><?= strtoupper(sanitize($h['language']??'fr')) ?></span></td>
        <td>
          <?php $action_labels = ['copied'=>'Copié','opened'=>'Ouvert','sent'=>'Envoyé']; ?>
          <span class="fk-badge <?= $h['action']==='sent'?'fk-badge-success':'fk-badge-info' ?>">
            <?= sanitize($action_labels[$h['action']] ?? $h['action']) ?>
          </span>
        </td>
        <td style="max-width:200px;font-size:11px;color:#6B7A72;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= sanitize(truncate($h['message'] ?? '', 60)) ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($active_tab === 'settings'): ?>
<div class="fk-card" style="max-width:600px">
  <div class="fk-card-header"><h3 class="fk-section-title">Paramètres WhatsApp</h3></div>
  <form onsubmit="saveWASettings(event)" style="display:flex;flex-direction:column;gap:14px">
    <div><label class="fk-label">Numéro WhatsApp admin</label>
      <input type="text" id="wa_admin" class="fk-input" value="<?= sanitize(get_setting('admin_whatsapp','')) ?>" placeholder="+212600000000"></div>
    <div><label class="fk-label">Langue par défaut</label>
      <select id="wa_lang" class="fk-select">
        <option value="fr" <?= get_setting('default_language','fr')==='fr'?'selected':'' ?>>Français</option>
        <option value="en" <?= get_setting('default_language','fr')==='en'?'selected':'' ?>>English</option>
        <option value="ar" <?= get_setting('default_language','fr')==='ar'?'selected':'' ?>>العربية</option>
      </select></div>
    <div><label class="fk-label">Signature automatique</label>
      <input type="text" id="wa_signature" class="fk-input" value="<?= sanitize(get_setting('whatsapp_signature','_Faiza Kids Concierge_ 🌿')) ?>"></div>
    <button type="submit" class="fk-btn fk-btn-primary" style="align-self:flex-start">Enregistrer</button>
    <div id="waSettingsMsg" style="display:none" class="fk-alert fk-alert-success"></div>
  </form>
</div>
<?php endif; ?>

<!-- View / Copy modal -->
<div id="viewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;max-width:560px;width:90%;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 id="viewModalTitle" style="font-size:16px;font-weight:700">Message</h3>
      <button onclick="document.getElementById('viewModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer">×</button>
    </div>
    <div style="padding:20px 24px">
      <div style="display:flex;gap:8px;margin-bottom:14px" id="viewLangTabs"></div>
      <div id="viewMsgContent" style="white-space:pre-wrap;font-size:14px;line-height:1.7;background:#F8FAF9;border-radius:10px;padding:14px;color:#1A2E24;word-break:break-word"></div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:8px">
      <button onclick="copyMsg()" class="fk-btn fk-btn-secondary">📋 Copier</button>
      <button onclick="document.getElementById('viewModal').style.display='none'" class="fk-btn fk-btn-secondary">Fermer</button>
    </div>
  </div>
</div>

<!-- Edit modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;overflow-y:auto;padding:24px">
  <div style="background:#fff;border-radius:20px;max-width:680px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 id="editModalTitle" style="font-size:16px;font-weight:700">Modifier template</h3>
      <button onclick="document.getElementById('editModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer">×</button>
    </div>
    <div style="padding:20px 24px">
      <input type="hidden" id="editTemplateKey" value="">
      <div style="margin-bottom:14px">
        <label class="fk-label">Français</label>
        <textarea id="editMsgFr" class="fk-input" rows="5"></textarea>
      </div>
      <div style="margin-bottom:14px">
        <label class="fk-label">English</label>
        <textarea id="editMsgEn" class="fk-input" rows="5"></textarea>
      </div>
      <div style="margin-bottom:14px">
        <label class="fk-label">العربية</label>
        <textarea id="editMsgAr" class="fk-input" rows="5" dir="rtl"></textarea>
      </div>
      <div style="background:#F0FAF5;border-radius:10px;padding:12px;font-size:12px;color:#2D6A4F">
        <strong>Variables disponibles :</strong><br>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
          <?php foreach (['{name}','{booking_reference}','{hotel}','{city}','{address}','{date}','{start_time}','{duration}','{children_count}','{child_names}','{price}','{payment_link}','{bank_details}','{babysitter_name}','{custom_note}'] as $v): ?>
          <code style="background:#D8F3DC;padding:2px 8px;border-radius:4px;cursor:pointer" onclick="insertVar('<?= $v ?>')"><?= $v ?></code>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:8px;justify-content:flex-end">
      <button onclick="document.getElementById('editModal').style.display='none'" class="fk-btn fk-btn-secondary">Annuler</button>
      <button onclick="saveTemplate()" class="fk-btn fk-btn-primary">Enregistrer</button>
    </div>
  </div>
</div>

<!-- Send/Use modal -->
<div id="sendModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;max-width:520px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 style="font-size:16px;font-weight:700">Envoyer un message</h3>
      <button onclick="document.getElementById('sendModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer">×</button>
    </div>
    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:12px">
      <div><label class="fk-label">Numéro WhatsApp destinataire</label>
        <input type="text" id="sendPhone" class="fk-input" placeholder="+212 6XX XXX XXX"></div>
      <div><label class="fk-label">Langue</label>
        <select id="sendLang" class="fk-select" onchange="updateSendPreview()">
          <option value="fr">🇫🇷 Français</option>
          <option value="en">🇬🇧 English</option>
          <option value="ar">🇲🇦 العربية</option>
        </select></div>
      <div><label class="fk-label">Note personnalisée</label>
        <input type="text" id="sendNote" class="fk-input" placeholder="Ex: Votre intervenante s'appelle Fatima." oninput="updateSendPreview()"></div>
      <div>
        <label class="fk-label">Aperçu du message</label>
        <div id="sendPreview" style="white-space:pre-wrap;font-size:13px;background:#F0FAF5;border-radius:10px;padding:12px;max-height:180px;overflow-y:auto;line-height:1.6;color:#1A2E24;word-break:break-word"></div>
      </div>
    </div>
    <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:8px">
      <button onclick="copySendMsg()" class="fk-btn fk-btn-secondary">📋 Copier</button>
      <button onclick="openWASend()" class="fk-btn fk-btn-success" style="background:#25D366;color:#fff;border:none">💬 Ouvrir WhatsApp</button>
      <button onclick="document.getElementById('sendModal').style.display='none'" class="fk-btn fk-btn-secondary">Fermer</button>
    </div>
  </div>
</div>

<script>
let currentViewLangs = {};
let currentViewLang  = 'fr';
let sendTemplateLangs = {};

function openViewModal(key, langs) {
  currentViewLangs = langs;
  currentViewLang  = 'fr';
  document.getElementById('viewModalTitle').textContent = <?= json_encode($template_names_fr) ?>[key] || key;
  const tabsDiv = document.getElementById('viewLangTabs');
  tabsDiv.innerHTML = Object.keys(langs).map(l=>
    `<button onclick="switchViewLang('${l}')" id="vl-${l}" class="fk-btn fk-btn-sm ${l==='fr'?'fk-btn-primary':'fk-btn-secondary'}">${l.toUpperCase()}</button>`
  ).join('');
  document.getElementById('viewMsgContent').textContent = langs['fr']?.message || '—';
  document.getElementById('viewModal').style.display = 'flex';
}

function switchViewLang(lang) {
  currentViewLang = lang;
  document.querySelectorAll('#viewLangTabs button').forEach(b=>{
    b.className = 'fk-btn fk-btn-sm ' + (b.id==='vl-'+lang ? 'fk-btn-primary' : 'fk-btn-secondary');
  });
  document.getElementById('viewMsgContent').textContent = currentViewLangs[lang]?.message || '—';
}

function copyMsg() {
  const txt = document.getElementById('viewMsgContent').textContent;
  navigator.clipboard.writeText(txt).then(()=>alert('Message copié !'));
}

function openEditModal(key, langs, name) {
  document.getElementById('editTemplateKey').value = key;
  document.getElementById('editModalTitle').textContent = 'Modifier · ' + name;
  document.getElementById('editMsgFr').value = langs['fr']?.message || '';
  document.getElementById('editMsgEn').value = langs['en']?.message || '';
  document.getElementById('editMsgAr').value = langs['ar']?.message || '';
  document.getElementById('editModal').style.display = 'flex';
}

async function saveTemplate() {
  const key = document.getElementById('editTemplateKey').value;
  const langs = {
    fr: document.getElementById('editMsgFr').value,
    en: document.getElementById('editMsgEn').value,
    ar: document.getElementById('editMsgAr').value,
  };
  const res = await fetch('/api/whatsapp?action=update_template', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({template_key: key, messages: langs})
  });
  const json = await res.json();
  if (json.success) { document.getElementById('editModal').style.display='none'; location.reload(); }
  else alert(json.error || 'Erreur.');
}

function insertVar(v) {
  const active = document.activeElement;
  if (active && active.tagName === 'TEXTAREA') {
    const s = active.selectionStart, e = active.selectionEnd;
    active.value = active.value.substring(0,s) + v + active.value.substring(e);
    active.selectionStart = active.selectionEnd = s + v.length;
  }
}

function openSendModal(key, langs) {
  sendTemplateLangs = langs;
  document.getElementById('sendPhone').value = '';
  document.getElementById('sendNote').value = '';
  document.getElementById('sendLang').value = 'fr';
  updateSendPreview();
  document.getElementById('sendModal').style.display = 'flex';
}

function updateSendPreview() {
  const lang = document.getElementById('sendLang').value;
  const note = document.getElementById('sendNote').value;
  let msg = sendTemplateLangs[lang]?.message || sendTemplateLangs['fr']?.message || '—';
  msg = msg.replace(/{custom_note}/g, note || '');
  document.getElementById('sendPreview').textContent = msg;
}

function copySendMsg() {
  const txt = document.getElementById('sendPreview').textContent;
  navigator.clipboard.writeText(txt).then(()=>alert('Message copié !'));
}

function openWASend() {
  const phone = document.getElementById('sendPhone').value.replace(/[^\d]/g,'');
  const msg   = document.getElementById('sendPreview').textContent;
  if (!phone) { alert('Veuillez entrer un numéro WhatsApp.'); return; }
  window.open('https://wa.me/'+phone+'?text='+encodeURIComponent(msg), '_blank');
}

async function saveWASettings(e) {
  e.preventDefault();
  const settings = {
    admin_whatsapp: document.getElementById('wa_admin').value,
    default_language: document.getElementById('wa_lang').value,
    whatsapp_signature: document.getElementById('wa_signature').value,
  };
  const res = await fetch('/api/settings?action=save', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({settings})});
  const json = await res.json();
  const el = document.getElementById('waSettingsMsg');
  el.textContent = json.success ? 'Paramètres enregistrés.' : (json.error||'Erreur.');
  el.className = 'fk-alert fk-alert-'+(json.success?'success':'error');
  el.style.display='block';
  setTimeout(()=>el.style.display='none',3000);
}
</script>

<?php include 'layout-bottom.php'; ?>
