<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$page_title    = 'Hôtels partenaires';
$page_subtitle = 'Gestion des hôtels partenaires, tarifs et pages publiques.';

$hotels = db_fetch_all(
    "SELECT h.*,
     (SELECT COUNT(*) FROM bookings b WHERE b.hotel_id=h.id) as booking_count,
     (SELECT COALESCE(SUM(b.final_price),0) FROM bookings b WHERE b.hotel_id=h.id AND b.service_date >= DATE_FORMAT(NOW(),'%Y-%m-01') AND b.status IN ('confirmed','completed') AND b.final_price IS NOT NULL) as monthly_revenue
     FROM hotels h ORDER BY h.sort_order ASC, h.id ASC",
    []
);

$base_url = defined('BASE_URL') ? BASE_URL : get_setting('base_url', '');
include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <button class="fk-btn fk-btn-primary" onclick="openHotelModal(null)">+ Ajouter un hôtel</button>
</div>

<div id="successMsg" style="display:none" class="fk-alert fk-alert-success" style="margin-bottom:16px"></div>
<div id="errorMsg"   style="display:none" class="fk-alert fk-alert-error"   style="margin-bottom:16px"></div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
<?php foreach ($hotels as $h):
  $pub_url = ($h['slug'] === 'city') ? $base_url.'/city' : $base_url.'/hotel/'.sanitize($h['slug']);
  $color   = htmlspecialchars($h['primary_color'] ?? '#2D6A4F');
?>
<div class="fk-card" style="border-top:4px solid <?= $color ?>">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <?php if (!empty($h['logo_path']) && file_exists(defined('UPLOAD_PATH') ? UPLOAD_PATH.'/hotels/'.basename($h['logo_path']) : '')): ?>
    <img src="<?= $base_url ?>/uploads/hotels/<?= htmlspecialchars(basename($h['logo_path'])) ?>" style="width:48px;height:48px;border-radius:10px;object-fit:contain;border:1px solid #E5EDE9">
    <?php else: ?>
    <div style="width:48px;height:48px;border-radius:10px;background:<?= $color ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700">
      <?= htmlspecialchars(substr($h['code'] ?? $h['name'], 0, 2)) ?>
    </div>
    <?php endif; ?>
    <div style="flex:1;min-width:0">
      <div style="font-size:15px;font-weight:700;color:#1A2E24;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($h['name']) ?></div>
      <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
        <span class="fk-badge" style="background:<?= $color ?>20;color:<?= $color ?>;font-family:monospace"><?= sanitize($h['code'] ?? '—') ?></span>
        <span class="fk-badge <?= $h['is_active'] ? 'fk-badge-success' : 'fk-badge-cancelled' ?>"><?= $h['is_active'] ? 'Actif' : 'Inactif' ?></span>
      </div>
    </div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
    <div style="background:#F8FAF9;border-radius:8px;padding:10px">
      <div style="font-size:11px;color:#6B7A72;text-transform:uppercase;font-weight:500">Réservations</div>
      <div style="font-size:20px;font-weight:700;color:#1A2E24"><?= $h['booking_count'] ?? 0 ?></div>
    </div>
    <div style="background:#F8FAF9;border-radius:8px;padding:10px">
      <div style="font-size:11px;color:#6B7A72;text-transform:uppercase;font-weight:500">Revenu ce mois</div>
      <div style="font-size:16px;font-weight:700;color:#2D6A4F"><?= format_price($h['monthly_revenue'] ?? 0) ?></div>
    </div>
  </div>
  <div style="font-size:12px;color:#6B7A72;margin-bottom:12px">
    Tarifs : <strong><?= format_price($h['day_rate']) ?>/j</strong> · <strong><?= format_price($h['night_rate']) ?>/n</strong>
  </div>
  <div style="font-size:11px;color:#9CA3AF;background:#F8FAF9;border-radius:6px;padding:6px 10px;margin-bottom:12px;word-break:break-all">
    <?= htmlspecialchars($pub_url) ?>
  </div>
  <div style="display:flex;gap:6px;flex-wrap:wrap">
    <button onclick="openHotelModal(<?= $h['id'] ?>)" class="fk-btn fk-btn-secondary fk-btn-sm">Modifier</button>
    <a href="<?= htmlspecialchars($pub_url) ?>" target="_blank" class="fk-btn fk-btn-secondary fk-btn-sm">Ouvrir</a>
    <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($pub_url) ?>').then(()=>showMsg('URL copiée !'))" class="fk-btn fk-btn-secondary fk-btn-sm">Copier URL</button>
    <button onclick="toggleHotel(<?= $h['id'] ?>, <?= $h['is_active'] ? 0 : 1 ?>)" class="fk-btn fk-btn-sm <?= $h['is_active'] ? 'fk-btn-warning' : 'fk-btn-success' ?>">
      <?= $h['is_active'] ? 'Désactiver' : 'Activer' ?>
    </button>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- Hotel edit/add modal -->
<div id="hotelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-start;justify-content:center;padding:32px 16px;overflow-y:auto">
  <div style="background:#fff;border-radius:20px;max-width:680px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 id="hotelModalTitle" style="font-size:16px;font-weight:700;color:#1A2E24">Hôtel</h3>
      <button onclick="closeHotelModal()" style="border:none;background:none;font-size:20px;cursor:pointer;color:#6B7A72">×</button>
    </div>
    <!-- Tabs -->
    <div style="display:flex;border-bottom:1px solid #E5EDE9;padding:0 24px">
      <?php foreach (['general'=>'Général','branding'=>'Branding','tarifs'=>'Tarifs & règles','messages'=>'Messages'] as $tab=>$lbl): ?>
      <button class="hotel-tab" data-tab="<?= $tab ?>" onclick="switchHotelTab('<?= $tab ?>')" style="padding:12px 16px;border:none;background:none;cursor:pointer;font-size:14px;font-weight:500;color:#6B7A72;border-bottom:2px solid transparent">
        <?= $lbl ?>
      </button>
      <?php endforeach; ?>
    </div>
    <form id="hotelForm" onsubmit="saveHotel(event)">
      <input type="hidden" id="hotel_id_field" name="id" value="">
      <div style="padding:20px 24px">

        <!-- General tab -->
        <div class="hotel-tab-content" id="tab-general">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div><label class="fk-label">Nom de l'hôtel</label><input type="text" name="name" id="f_name" class="fk-input" required></div>
            <div><label class="fk-label">Code hôtel</label><input type="text" name="code" id="f_code" class="fk-input" placeholder="Ex: FMT"></div>
            <div><label class="fk-label">Slug URL</label><input type="text" name="slug" id="f_slug" class="fk-input" placeholder="fairmont-taghazout-bay"></div>
            <div><label class="fk-label">Email contact</label><input type="email" name="email" id="f_email" class="fk-input"></div>
            <div><label class="fk-label">Email notifications</label><input type="email" name="notification_email" id="f_notification_email" class="fk-input"></div>
            <div><label class="fk-label">Téléphone / WhatsApp</label><input type="text" name="phone" id="f_phone" class="fk-input"></div>
            <div style="grid-column:span 2"><label class="fk-label">Adresse</label><input type="text" name="address" id="f_address" class="fk-input"></div>
            <div style="grid-column:span 2">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="checkbox" name="is_active" id="f_is_active" value="1" checked style="accent-color:#2D6A4F;width:18px;height:18px">
                <span class="fk-label" style="margin:0">Hôtel actif</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Branding tab -->
        <div class="hotel-tab-content" id="tab-branding" style="display:none">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div><label class="fk-label">Couleur principale</label><input type="color" name="primary_color" id="f_primary_color" class="fk-input" style="height:44px;padding:4px" value="#2D6A4F"></div>
            <div><label class="fk-label">Couleur secondaire</label><input type="color" name="secondary_color" id="f_secondary_color" class="fk-input" style="height:44px;padding:4px" value="#52B788"></div>
            <div><label class="fk-label">Couleur accent</label><input type="color" name="accent_color" id="f_accent_color" class="fk-input" style="height:44px;padding:4px" value="#E8C342"></div>
            <div style="grid-column:span 2">
              <label class="fk-label">Logo hôtel</label>
              <div style="border:2px dashed #E5EDE9;border-radius:10px;padding:20px;text-align:center;cursor:pointer" onclick="document.getElementById('logoUpload').click()">
                <div id="logoPreview" style="font-size:13px;color:#6B7A72">Cliquez pour choisir un logo (PNG/JPG/SVG)</div>
              </div>
              <input type="file" id="logoUpload" accept=".png,.jpg,.jpeg,.svg,.webp" style="display:none" onchange="previewLogo(this)">
            </div>
            <div style="grid-column:span 2"><label class="fk-label">Titre public</label><input type="text" name="public_title" id="f_public_title" class="fk-input" placeholder="Réservez une babysitter de confiance"></div>
            <div style="grid-column:span 2"><label class="fk-label">Texte public</label><textarea name="public_text" id="f_public_text" class="fk-input" rows="3"></textarea></div>
          </div>
        </div>

        <!-- Tarifs tab -->
        <div class="hotel-tab-content" id="tab-tarifs" style="display:none">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div><label class="fk-label">Tarif jour (DH)</label><input type="number" name="day_rate" id="f_day_rate" class="fk-input" min="0" step="10"></div>
            <div><label class="fk-label">Tarif soir/nuit (DH)</label><input type="number" name="night_rate" id="f_night_rate" class="fk-input" min="0" step="10"></div>
            <div><label class="fk-label">Début période soir</label><input type="time" name="night_start_hour" id="f_night_start_hour" class="fk-input"></div>
            <div><label class="fk-label">Fin période nuit</label><input type="time" name="night_end_hour" id="f_night_end_hour" class="fk-input"></div>
            <div><label class="fk-label">Délai min. réservation (h)</label><input type="number" name="min_booking_hours" id="f_min_booking_hours" class="fk-input" min="1" value="24"></div>
            <div><label class="fk-label">Délai min. annulation (h)</label><input type="number" name="cancellation_hours" id="f_cancellation_hours" class="fk-input" min="1" value="3"></div>
          </div>
          <div class="fk-alert fk-alert-info" style="margin-top:14px;font-size:13px">Les tarifs sont utilisés pour les estimations internes. Ils ne sont pas affichés au client.</div>
        </div>

        <!-- Messages tab -->
        <div class="hotel-tab-content" id="tab-messages" style="display:none">
          <div style="display:flex;flex-direction:column;gap:14px">
            <div><label class="fk-label">Message intro WhatsApp</label><textarea name="whatsapp_intro" id="f_whatsapp_intro" class="fk-input" rows="3"></textarea></div>
            <div><label class="fk-label">Message confirmation</label><textarea name="whatsapp_confirmation" id="f_whatsapp_confirmation" class="fk-input" rows="3"></textarea></div>
            <div><label class="fk-label">Message rappel (avant service)</label><textarea name="whatsapp_reminder" id="f_whatsapp_reminder" class="fk-input" rows="3"></textarea></div>
          </div>
        </div>
      </div>

      <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="closeHotelModal()" class="fk-btn fk-btn-secondary">Annuler</button>
        <button type="submit" id="saveHotelBtn" class="fk-btn fk-btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>
const HOTELS_DATA = <?= json_encode($hotels) ?>;

function showMsg(msg, type='success') {
  const el = document.getElementById(type==='success'?'successMsg':'errorMsg');
  el.textContent = msg; el.style.display='block';
  setTimeout(()=>el.style.display='none',3000);
}

function switchHotelTab(tab) {
  document.querySelectorAll('.hotel-tab-content').forEach(el=>el.style.display='none');
  document.querySelectorAll('.hotel-tab').forEach(el=>{
    el.style.borderBottomColor='transparent';el.style.color='#6B7A72';
  });
  document.getElementById('tab-'+tab).style.display='block';
  const btn = document.querySelector(`.hotel-tab[data-tab="${tab}"]`);
  if (btn) { btn.style.borderBottomColor='#2D6A4F'; btn.style.color='#2D6A4F'; }
}

function openHotelModal(hotelId) {
  const modal = document.getElementById('hotelModal');
  modal.style.display = 'flex';
  switchHotelTab('general');
  if (!hotelId) {
    document.getElementById('hotelModalTitle').textContent = 'Nouvel hôtel';
    document.getElementById('hotelForm').reset();
    document.getElementById('hotel_id_field').value = '';
    return;
  }
  const h = HOTELS_DATA.find(x=>x.id==hotelId);
  if (!h) return;
  document.getElementById('hotelModalTitle').textContent = 'Modifier · '+h.name;
  document.getElementById('hotel_id_field').value = h.id;
  const fields = ['name','code','slug','email','notification_email','phone','address','primary_color','secondary_color','accent_color','public_title','public_text','day_rate','night_rate','min_booking_hours','cancellation_hours','whatsapp_intro','whatsapp_confirmation','whatsapp_reminder'];
  fields.forEach(f=>{const el=document.getElementById('f_'+f); if(el) el.value=h[f]??'';});
  const timeFields = ['night_start_hour','night_end_hour'];
  timeFields.forEach(f=>{const el=document.getElementById('f_'+f);if(el) el.value=(h[f]||'').substring(0,5);});
  document.getElementById('f_is_active').checked = h.is_active==1;
}

function closeHotelModal() { document.getElementById('hotelModal').style.display='none'; }

async function saveHotel(e) {
  e.preventDefault();
  const form = document.getElementById('hotelForm');
  const data = new FormData(form);
  const obj = {};
  data.forEach((v,k)=>obj[k]=v);
  obj.is_active = form.querySelector('#f_is_active').checked ? 1 : 0;
  const hid = obj.id;
  const action = hid ? 'update&id='+hid : 'create';
  try {
    const res = await fetch('/api/hotels?action='+action, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(obj)});
    const json = await res.json();
    if (json.success) { showMsg(hid?'Hôtel mis à jour.':'Hôtel créé.'); closeHotelModal(); setTimeout(()=>location.reload(),1000); }
    else showMsg(json.error||'Erreur.','error');
  } catch(err) { showMsg('Erreur réseau.','error'); }
}

async function toggleHotel(id, newState) {
  const res = await fetch('/api/hotels?action=toggle&id='+id, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({is_active:newState})});
  const json = await res.json();
  if (json.success) location.reload();
  else showMsg(json.error||'Erreur.','error');
}

function previewLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('logoPreview').innerHTML = `<img src="${e.target.result}" style="max-height:60px;border-radius:6px">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

<?php include 'layout-bottom.php'; ?>
