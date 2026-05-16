<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page_title    = 'Paiements';
$page_subtitle = 'Suivi des virements bancaires et validation des paiements.';

$filter_status = $_GET['payment_status'] ?? '';
$params = [];
$where  = '1=1';
if ($filter_status) { $where .= ' AND b.payment_status = ?'; $params[] = $filter_status; }

$bookings = db_fetch_all(
    "SELECT b.*, h.name as hotel_name,
     (SELECT COUNT(*) FROM payment_proofs pp WHERE pp.booking_id=b.id) as proof_count
     FROM bookings b
     LEFT JOIN hotels h ON b.hotel_id = h.id
     WHERE $where
     ORDER BY b.updated_at DESC",
    $params
);

$kpi_pending   = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE payment_status IN ('requested','proof_sent')", [])['c'] ?? 0;
$kpi_proofs    = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE payment_status='proof_sent'", [])['c'] ?? 0;
$kpi_validated = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE payment_status='validated'", [])['c'] ?? 0;
$kpi_total     = db_fetch("SELECT COALESCE(SUM(final_price),0) as t FROM bookings WHERE payment_status='validated' AND final_price IS NOT NULL", [])['t'] ?? 0;

$bank_name = get_setting('bank_name', 'CIH Bank');
$bank_rib  = get_setting('bank_rib', '');
$bank_acc  = get_setting('bank_account_name', 'Faiza Multiservice');

$payment_status_labels = [
    'not_required' => 'Non requis',
    'pending'      => 'En attente',
    'requested'    => 'Demande envoyée',
    'proof_sent'   => 'Preuve reçue',
    'validated'    => 'Validé',
    'refused'      => 'Refusé',
    'refunded'     => 'Remboursé',
];
$payment_status_badge = [
    'not_required' => 'fk-badge-cancelled',
    'pending'      => 'fk-badge-warning',
    'requested'    => 'fk-badge-info',
    'proof_sent'   => 'fk-badge-gold',
    'validated'    => 'fk-badge-success',
    'refused'      => 'fk-badge-cancelled',
    'refunded'     => 'fk-badge-info',
];

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <a href="/api/export?type=payments" class="fk-btn fk-btn-secondary">⬇ Exporter CSV</a>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#F59E0B"><?= $kpi_pending ?></div>
    <div class="fk-kpi-label">En attente de vérification</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#0EA5E9"><?= $kpi_proofs ?></div>
    <div class="fk-kpi-label">Preuves reçues</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#22C55E"><?= $kpi_validated ?></div>
    <div class="fk-kpi-label">Paiements validés</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:22px;font-weight:700;color:#2D6A4F"><?= format_price($kpi_total) ?></div>
    <div class="fk-kpi-label">Total validé</div>
  </div>
</div>

<!-- Filter pills -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <?php $filters = [''=> 'Tous', 'pending'=>'En attente', 'requested'=>'Demande envoyée', 'proof_sent'=>'Preuve reçue', 'validated'=>'Validé', 'refused'=>'Refusé']; ?>
  <?php foreach ($filters as $val => $lbl): ?>
  <a href="?payment_status=<?= $val ?>" class="fk-btn fk-btn-sm <?= $filter_status===$val ? 'fk-btn-primary' : 'fk-btn-secondary' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<div id="actionMsg" class="fk-alert" style="display:none;margin-bottom:12px"></div>

<div class="fk-card">
  <?php if (empty($bookings)): ?>
  <div class="fk-empty-state">
    <div style="font-size:48px;margin-bottom:16px">💳</div>
    <div class="fk-empty-title">Aucun paiement trouvé</div>
    <div class="fk-empty-text">Les paiements apparaîtront ici une fois les réservations créées.</div>
  </div>
  <?php else: ?>
  <div class="fk-table-wrapper">
    <table class="fk-table">
      <thead><tr>
        <th>Référence</th><th>Client</th><th>Type / Lieu</th>
        <th>Montant</th><th>Statut paiement</th><th>Preuve</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($bookings as $bk): ?>
      <tr>
        <td>
          <a href="/admin/bookings/view/<?= $bk['id'] ?>" style="font-family:monospace;font-weight:700;color:#2D6A4F;text-decoration:none">
            <?= sanitize($bk['reference']) ?>
          </a>
          <div style="font-size:11px;color:#9CA3AF"><?= sanitize($bk['service_date']) ?></div>
        </td>
        <td>
          <div style="font-weight:500"><?= sanitize($bk['client_name']) ?></div>
          <a href="https://wa.me/<?= preg_replace('/[^\d]/','',$bk['client_whatsapp']) ?>" target="_blank" style="font-size:12px;color:#25D366">
            <?= sanitize($bk['client_whatsapp']) ?>
          </a>
        </td>
        <td>
          <span class="fk-badge <?= $bk['type']==='hotel'?'fk-badge-hotel':'fk-badge-city' ?>"><?= $bk['type']==='hotel'?'Hôtel':'City' ?></span>
          <div style="font-size:12px;color:#6B7A72;margin-top:4px"><?= sanitize($bk['hotel_name'] ?? ($bk['city']?:'—')) ?></div>
        </td>
        <td>
          <?php if ($bk['final_price']): ?>
          <span style="font-weight:700;color:#1A2E24"><?= format_price($bk['final_price']) ?></span>
          <?php else: ?>
          <span class="fk-badge fk-badge-warning">Devis en attente</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="fk-badge <?= $payment_status_badge[$bk['payment_status']] ?? '' ?>">
            <?= sanitize($payment_status_labels[$bk['payment_status']] ?? $bk['payment_status']) ?>
          </span>
        </td>
        <td>
          <?php if ($bk['proof_count'] > 0): ?>
          <button onclick="viewProof(<?= $bk['id'] ?>)" class="fk-btn fk-btn-secondary fk-btn-sm">📎 Voir (<?= $bk['proof_count'] ?>)</button>
          <?php else: ?>
          <span style="color:#9CA3AF;font-size:13px">—</span>
          <?php endif; ?>
        </td>
        <td>
          <div style="display:flex;gap:4px;flex-wrap:wrap">
            <?php if ($bk['payment_status'] === 'pending'): ?>
            <button onclick="payAction(<?= $bk['id'] ?>,'request')" class="fk-btn fk-btn-sm fk-btn-secondary">Demander paiement</button>
            <?php endif; ?>
            <?php if ($bk['payment_status'] === 'proof_sent'): ?>
            <button onclick="payAction(<?= $bk['id'] ?>,'validate')" class="fk-btn fk-btn-sm fk-btn-success">✓ Valider</button>
            <button onclick="payAction(<?= $bk['id'] ?>,'refuse')" class="fk-btn fk-btn-sm fk-btn-danger">✗ Refuser</button>
            <?php endif; ?>
            <a href="/admin/bookings/view/<?= $bk['id'] ?>" class="fk-btn fk-btn-sm fk-btn-secondary">Voir</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Bank details card -->
<div class="fk-card" style="margin-top:16px">
  <div class="fk-card-header">
    <h3 class="fk-section-title">Coordonnées bancaires</h3>
    <a href="/admin/settings?tab=paiement" class="fk-btn fk-btn-secondary fk-btn-sm">Modifier</a>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
    <div><div style="font-size:11px;color:#6B7A72;font-weight:600;text-transform:uppercase">Banque</div><div style="font-size:15px;font-weight:600;margin-top:4px"><?= sanitize($bank_name) ?></div></div>
    <div><div style="font-size:11px;color:#6B7A72;font-weight:600;text-transform:uppercase">Titulaire</div><div style="font-size:15px;font-weight:600;margin-top:4px"><?= sanitize($bank_acc) ?></div></div>
    <div><div style="font-size:11px;color:#6B7A72;font-weight:600;text-transform:uppercase">RIB / IBAN</div><div style="font-size:15px;font-weight:600;margin-top:4px;font-family:monospace"><?= sanitize($bank_rib ?: '— Non configuré') ?></div></div>
  </div>
</div>

<!-- Proof modal -->
<div id="proofModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;max-width:500px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between">
      <h3 style="font-size:16px;font-weight:700">Preuves de paiement</h3>
      <button onclick="document.getElementById('proofModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer">×</button>
    </div>
    <div id="proofContent" style="padding:20px">Chargement...</div>
  </div>
</div>

<script>
function showMsg(msg, type='success') {
  const el = document.getElementById('actionMsg');
  el.textContent = msg;
  el.className = 'fk-alert fk-alert-'+type;
  el.style.display = 'block';
  setTimeout(()=>el.style.display='none', 3000);
}

async function payAction(bookingId, action) {
  const actions = {request:'request',validate:'validate',refuse:'refuse'};
  const labels  = {request:'Demande envoyée.',validate:'Paiement validé.',refuse:'Paiement refusé.'};
  if (!actions[action]) return;
  const res = await fetch('/api/payments?action='+action+'&booking_id='+bookingId, {method:'POST',headers:{'Content-Type':'application/json'},body:'{}'});
  const json = await res.json();
  if (json.success) { showMsg(labels[action]||'Action effectuée.'); setTimeout(()=>location.reload(),1500); }
  else showMsg(json.error||'Erreur.','error');
}

async function viewProof(bookingId) {
  document.getElementById('proofModal').style.display = 'flex';
  document.getElementById('proofContent').innerHTML = 'Chargement...';
  const res = await fetch('/api/payments?action=proof&booking_id='+bookingId);
  const json = await res.json();
  if (json.success && json.proofs && json.proofs.length) {
    const baseUrl = '<?= htmlspecialchars($base_url ?? '') ?>';
    document.getElementById('proofContent').innerHTML = json.proofs.map(p=>`
      <div style="margin-bottom:16px">
        <div style="font-size:12px;color:#6B7A72;margin-bottom:8px">${p.uploaded_at} — ${p.status}</div>
        ${p.file_type && p.file_type.startsWith('image/') ?
          `<img src="${baseUrl}/uploads/proofs/${p.file_name||p.file_path}" style="max-width:100%;border-radius:8px;border:1px solid #E5EDE9">` :
          `<a href="${baseUrl}/uploads/proofs/${p.file_name||p.file_path}" target="_blank" class="fk-btn fk-btn-secondary fk-btn-sm">📄 Voir le fichier</a>`
        }
      </div>
    `).join('');
  } else {
    document.getElementById('proofContent').innerHTML = '<div style="text-align:center;color:#6B7A72;padding:20px">Aucune preuve disponible.</div>';
  }
}
</script>

<?php include 'layout-bottom.php'; ?>
