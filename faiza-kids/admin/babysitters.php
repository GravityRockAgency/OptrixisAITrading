<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page_title    = 'Babysitters';
$page_subtitle = 'Gestion de l\'équipe de babysitters.';

$filter_status = $_GET['status'] ?? '';
$params = [];
$where  = '';
if ($filter_status) {
    $where = "WHERE bs.status = ?";
    $params[] = $filter_status;
}

$babysitters = db_fetch_all(
    "SELECT bs.*, (SELECT COUNT(*) FROM bookings b WHERE b.babysitter_id=bs.id) as booking_count
     FROM babysitters bs $where ORDER BY bs.full_name ASC",
    $params
);

$hotels = db_fetch_all("SELECT id, name FROM hotels WHERE is_active=1 ORDER BY sort_order", []);

$status_labels = ['available'=>'Disponible','busy'=>'Occupée','vacation'=>'Congé','inactive'=>'Inactive'];
$status_colors = ['available'=>'fk-badge-success','busy'=>'fk-badge-warning','vacation'=>'fk-badge-info','inactive'=>'fk-badge-cancelled'];

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <button class="fk-btn fk-btn-primary" onclick="openBabyModal(null)">+ Ajouter une babysitter</button>
</div>

<!-- Status filter pills -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <?php $filters = [''=> 'Toutes', 'available'=>'Disponibles', 'busy'=>'Occupées', 'vacation'=>'Congé', 'inactive'=>'Inactives']; ?>
  <?php foreach ($filters as $val => $lbl): ?>
  <a href="?status=<?= $val ?>" class="fk-btn fk-btn-sm <?= $filter_status===$val ? 'fk-btn-primary' : 'fk-btn-secondary' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<div id="successMsg" class="fk-alert fk-alert-success" style="display:none;margin-bottom:12px"></div>

<div class="fk-card">
  <?php if (empty($babysitters)): ?>
  <div class="fk-empty-state">
    <div style="font-size:48px;margin-bottom:16px">👩</div>
    <div class="fk-empty-title">Aucune babysitter trouvée</div>
    <div class="fk-empty-text">Ajoutez votre première babysitter pour commencer.</div>
    <button class="fk-btn fk-btn-primary" style="margin-top:16px" onclick="openBabyModal(null)">Ajouter une babysitter</button>
  </div>
  <?php else: ?>
  <div class="fk-table-wrapper">
    <table class="fk-table">
      <thead><tr>
        <th>Nom</th><th>Téléphone</th><th>Langues</th><th>Zones</th>
        <th>Réservations</th><th>Statut</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($babysitters as $bs): ?>
      <tr>
        <td>
          <div style="font-weight:600;color:#1A2E24"><?= sanitize($bs['full_name']) ?></div>
          <?php if ($bs['certifications']): ?>
          <div style="font-size:11px;color:#6B7A72"><?= sanitize(truncate($bs['certifications'],40)) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($bs['phone']): ?>
          <a href="https://wa.me/<?= preg_replace('/[^\d]/','',$bs['phone']) ?>" target="_blank" style="color:#25D366;text-decoration:none">
            📱 <?= sanitize($bs['phone']) ?>
          </a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td style="font-size:13px"><?= sanitize($bs['languages'] ?? '—') ?></td>
        <td style="font-size:13px"><?= sanitize($bs['zones'] ?? '—') ?></td>
        <td>
          <span class="fk-badge"><?= $bs['booking_count'] ?? 0 ?> réserv.</span>
        </td>
        <td>
          <span class="fk-badge <?= $status_colors[$bs['status']] ?? 'fk-badge-cancelled' ?>">
            <?= sanitize($status_labels[$bs['status']] ?? $bs['status']) ?>
          </span>
        </td>
        <td>
          <div style="display:flex;gap:6px">
            <button onclick="openBabyModal(<?= $bs['id'] ?>)" class="fk-btn fk-btn-secondary fk-btn-sm">Modifier</button>
            <button onclick="manageAvailability(<?= $bs['id'] ?>)" class="fk-btn fk-btn-secondary fk-btn-sm">Gérer</button>
            <?php if ($bs['status'] !== 'vacation'): ?>
            <button onclick="setBabyStatus(<?= $bs['id'] ?>,'vacation')" class="fk-btn fk-btn-warning fk-btn-sm">Congé</button>
            <?php else: ?>
            <button onclick="setBabyStatus(<?= $bs['id'] ?>,'available')" class="fk-btn fk-btn-success fk-btn-sm">Activer</button>
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

<!-- Add/Edit Babysitter Modal -->
<div id="babyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-start;justify-content:center;padding:32px 16px;overflow-y:auto">
  <div style="background:#fff;border-radius:20px;max-width:580px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 id="babyModalTitle" style="font-size:16px;font-weight:700;color:#1A2E24">Babysitter</h3>
      <button onclick="document.getElementById('babyModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer;color:#6B7A72">×</button>
    </div>
    <form id="babyForm" onsubmit="saveBabysitter(event)">
      <input type="hidden" id="baby_id" name="id" value="">
      <div style="padding:20px 24px;display:grid;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div style="grid-column:span 2"><label class="fk-label">Nom complet <span style="color:#EF4444">*</span></label>
            <input type="text" name="full_name" id="b_full_name" class="fk-input" required></div>
          <div><label class="fk-label">Téléphone / WhatsApp</label>
            <input type="text" name="phone" id="b_phone" class="fk-input" placeholder="+212 6XX XXX XXX"></div>
          <div><label class="fk-label">Statut</label>
            <select name="status" id="b_status" class="fk-select">
              <?php foreach ($status_labels as $v=>$l): ?>
              <option value="<?= $v ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select></div>
          <div><label class="fk-label">Langues parlées</label>
            <input type="text" name="languages" id="b_languages" class="fk-input" placeholder="FR, EN, AR"></div>
          <div><label class="fk-label">Zones de couverture</label>
            <input type="text" name="zones" id="b_zones" class="fk-input" placeholder="Agadir, Taghazout..."></div>
          <div><label class="fk-label">Certifications</label>
            <input type="text" name="certifications" id="b_certifications" class="fk-input" placeholder="Premiers secours, PSC1..."></div>
          <div><label class="fk-label">Années d'expérience</label>
            <input type="number" name="experience_years" id="b_experience_years" class="fk-input" min="0" value="0"></div>
          <div style="grid-column:span 2"><label class="fk-label">Notes</label>
            <textarea name="notes" id="b_notes" class="fk-input" rows="2" placeholder="Informations générales..."></textarea></div>
          <div style="grid-column:span 2"><label class="fk-label">Notes internes</label>
            <textarea name="internal_notes" id="b_internal_notes" class="fk-input" rows="2" placeholder="Notes visibles par l'équipe uniquement..."></textarea></div>
        </div>
        <div>
          <label class="fk-label" style="margin-bottom:10px;display:block">Hôtels autorisés</label>
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($hotels as $h): ?>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px;border:1px solid #E5EDE9;border-radius:8px">
              <input type="checkbox" name="hotels[]" value="<?= $h['id'] ?>" class="hotel-check" style="accent-color:#2D6A4F">
              <span style="font-size:14px;color:#1A2E24"><?= sanitize($h['name']) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" name="is_active" id="b_is_active" value="1" checked style="accent-color:#2D6A4F;width:18px;height:18px">
          <span class="fk-label" style="margin:0">Active</span>
        </label>
      </div>
      <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('babyModal').style.display='none'" class="fk-btn fk-btn-secondary">Annuler</button>
        <button type="submit" class="fk-btn fk-btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Manage availability modal -->
<div id="manageModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 id="manageModalTitle" style="font-size:16px;font-weight:700">Gérer disponibilité</h3>
      <button onclick="document.getElementById('manageModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer;color:#6B7A72">×</button>
    </div>
    <div style="padding:20px 24px">
      <div id="manageModalContent">Chargement...</div>
    </div>
  </div>
</div>

<script>
const BABY_DATA = <?= json_encode($babysitters) ?>;
const BABY_HOTELS = <?= json_encode($hotels) ?>;

function openBabyModal(id) {
  document.getElementById('babyModal').style.display = 'flex';
  document.querySelectorAll('.hotel-check').forEach(cb=>cb.checked=false);
  if (!id) {
    document.getElementById('babyModalTitle').textContent = 'Nouvelle babysitter';
    document.getElementById('babyForm').reset();
    document.getElementById('baby_id').value = '';
    return;
  }
  const b = BABY_DATA.find(x=>x.id==id);
  if (!b) return;
  document.getElementById('babyModalTitle').textContent = 'Modifier · '+b.full_name;
  document.getElementById('baby_id').value = b.id;
  ['full_name','phone','languages','zones','certifications','experience_years','notes','internal_notes'].forEach(f=>{
    const el=document.getElementById('b_'+f); if(el) el.value=b[f]??'';
  });
  document.getElementById('b_status').value = b.status||'available';
  document.getElementById('b_is_active').checked = b.is_active==1;
  // Load assigned hotels
  fetch('/api/babysitters?action=get&id='+id)
    .then(r=>r.json())
    .then(res=>{
      if(res.data && res.data.hotel_ids) {
        res.data.hotel_ids.forEach(hid=>{
          const cb=document.querySelector(`.hotel-check[value="${hid}"]`);
          if(cb) cb.checked=true;
        });
      }
    });
}

async function saveBabysitter(e) {
  e.preventDefault();
  const form = document.getElementById('babyForm');
  const data = new FormData(form);
  const obj = {};
  data.forEach((v,k)=>{
    if(k==='hotels[]') { obj.hotels = obj.hotels||[]; obj.hotels.push(v); }
    else obj[k]=v;
  });
  obj.is_active = form.querySelector('#b_is_active').checked ? 1 : 0;
  if(!obj.hotels) obj.hotels = [];
  const bid = obj.id;
  const action = bid ? 'update&id='+bid : 'create';
  const res = await fetch('/api/babysitters?action='+action,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(obj)});
  const json = await res.json();
  if(json.success){
    document.getElementById('successMsg').textContent = bid?'Babysitter mise à jour.':'Babysitter créée.';
    document.getElementById('successMsg').style.display='block';
    document.getElementById('babyModal').style.display='none';
    setTimeout(()=>location.reload(),1200);
  } else alert(json.error||'Erreur.');
}

async function setBabyStatus(id, status) {
  const res = await fetch('/api/babysitters?action=status&id='+id,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({status})});
  const json = await res.json();
  if(json.success) location.reload();
  else alert(json.error||'Erreur.');
}

function manageAvailability(id) {
  const b = BABY_DATA.find(x=>x.id==id);
  document.getElementById('manageModal').style.display='flex';
  document.getElementById('manageModalTitle').textContent = 'Gérer · '+(b?b.full_name:'');
  document.getElementById('manageModalContent').innerHTML = `
    <p style="font-size:14px;color:#6B7A72;margin-bottom:16px">Statut actuel : <strong>${b?b.status:'—'}</strong></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      ${['available','busy','vacation','inactive'].map(s=>`
        <button onclick="setBabyStatus(${id},'${s}');document.getElementById('manageModal').style.display='none'"
          class="fk-btn fk-btn-sm fk-btn-secondary">${{available:'Disponible',busy:'Occupée',vacation:'Congé',inactive:'Inactive'}[s]}</button>
      `).join('')}
    </div>
    <hr style="margin:16px 0;border:none;border-top:1px solid #E5EDE9">
    <p style="font-size:13px;color:#6B7A72">Pour gérer les hôtels assignés, utilisez le bouton <strong>Modifier</strong>.</p>
    <button onclick="document.getElementById('manageModal').style.display='none';openBabyModal(${id})" class="fk-btn fk-btn-primary fk-btn-sm" style="margin-top:8px">Modifier</button>
  `;
}
</script>

<?php include 'layout-bottom.php'; ?>
