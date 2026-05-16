<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

// Extract booking ID from URL: /admin/bookings/view/{id}
$uri_parts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$booking_id = 0;
foreach ($uri_parts as $i => $part) {
    if ($part === 'view' && isset($uri_parts[$i+1])) {
        $booking_id = (int)$uri_parts[$i+1];
        break;
    }
}

if (!$booking_id) {
    header('Location: /admin/bookings');
    exit;
}

$booking = db_fetch(
    "SELECT b.*, h.name as hotel_name, h.slug as hotel_slug, h.code as hotel_code,
            bs.name as babysitter_name, bs.phone as babysitter_phone
     FROM bookings b
     LEFT JOIN hotels h ON b.hotel_id = h.id
     LEFT JOIN babysitters bs ON b.babysitter_id = bs.id
     WHERE b.id = ?",
    [$booking_id]
);

if (!$booking) {
    header('Location: /admin/bookings');
    exit;
}

$children = db_fetch_all("SELECT * FROM children WHERE booking_id = ? ORDER BY id", [$booking_id]);
$proofs   = db_fetch_all("SELECT * FROM payment_proofs WHERE booking_id = ? ORDER BY created_at DESC", [$booking_id]);
$history  = db_fetch_all(
    "SELECT wh.*, b.reference FROM whatsapp_history wh LEFT JOIN bookings b ON wh.booking_id=b.id WHERE wh.booking_id=? ORDER BY wh.sent_at DESC",
    [$booking_id]
);
$activity = db_fetch_all(
    "SELECT al.*, a.username FROM activity_log al LEFT JOIN admins a ON al.admin_id=a.id WHERE al.booking_id=? ORDER BY al.created_at DESC",
    [$booking_id]
);

$babysitters = db_fetch_all("SELECT id, name FROM babysitters WHERE is_active=1 ORDER BY name");

$admin_wa = get_setting('admin_whatsapp', '+212600000000');
$base_url = defined('BASE_URL') ? BASE_URL : '';

$status_labels = [
    'pending'    => 'En attente',
    'confirmed'  => 'Confirmée',
    'in_progress'=> 'En cours',
    'completed'  => 'Terminée',
    'cancelled'  => 'Annulée',
];
$status_colors = [
    'pending'    => 'warning',
    'confirmed'  => 'success',
    'in_progress'=> 'info',
    'completed'  => 'secondary',
    'cancelled'  => 'danger',
];
$payment_labels = [
    'not_required'   => 'Non requis',
    'pending'        => 'En attente',
    'requested'      => 'Demandé',
    'proof_uploaded' => 'Preuve envoyée',
    'validated'      => 'Validé',
    'refused'        => 'Refusé',
];

$page_title = 'Réservation ' . $booking['reference'];
$extra_js = '';
include __DIR__ . '/layout-top.php';
?>

<div class="fk-content-header">
  <div class="fk-content-header-left">
    <a href="/admin/bookings" class="fk-btn fk-btn-ghost fk-btn-sm" style="margin-right:8px">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Retour
    </a>
    <div>
      <h1 class="fk-page-title">Réservation <?= sanitize($booking['reference']) ?></h1>
      <p class="fk-page-sub"><?= sanitize(format_date_fr($booking['service_date'])) ?> · <?= sanitize($booking['start_time']) ?> · <?= format_duration($booking['duration_minutes'] ?? 0) ?></p>
    </div>
  </div>
  <div class="fk-content-header-right">
    <span class="fk-badge fk-badge-<?= $status_colors[$booking['status']] ?? 'secondary' ?> fk-badge-lg">
      <?= sanitize($status_labels[$booking['status']] ?? $booking['status']) ?>
    </span>
    <button class="fk-btn fk-btn-wa" onclick="openWAModal(<?= $booking_id ?>)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.524 5.847L.057 23.5l5.806-1.524A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.817 9.817 0 0 1-5.002-1.367l-.359-.213-3.724.977.994-3.635-.233-.374A9.817 9.817 0 0 1 2.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/></svg>
      WhatsApp
    </button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start">

<!-- LEFT COLUMN -->
<div>

  <!-- Client Info -->
  <div class="fk-card" style="margin-bottom:20px">
    <div class="fk-card-header">
      <h3 class="fk-card-title">👤 Informations client</h3>
    </div>
    <div class="fk-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <div class="fk-field-label">Nom complet</div>
          <div class="fk-field-value"><?= sanitize($booking['client_name']) ?></div>
        </div>
        <div>
          <div class="fk-field-label">Téléphone</div>
          <div class="fk-field-value">
            <a href="<?= htmlspecialchars(get_whatsapp_link($booking['client_phone'], 'Bonjour ' . $booking['client_name'] . ', concernant votre réservation ' . $booking['reference'] . '...')) ?>" target="_blank" class="fk-link-green">
              <?= sanitize($booking['client_phone']) ?>
            </a>
          </div>
        </div>
        <?php if (!empty($booking['client_email'])): ?>
        <div>
          <div class="fk-field-label">Email</div>
          <div class="fk-field-value"><a href="mailto:<?= sanitize($booking['client_email']) ?>" class="fk-link-green"><?= sanitize($booking['client_email']) ?></a></div>
        </div>
        <?php endif; ?>
        <div>
          <div class="fk-field-label">Langue préférée</div>
          <div class="fk-field-value"><?= strtoupper(sanitize($booking['client_lang'] ?? 'fr')) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Service Details -->
  <div class="fk-card" style="margin-bottom:20px">
    <div class="fk-card-header">
      <h3 class="fk-card-title">📋 Détails de la prestation</h3>
    </div>
    <div class="fk-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <div class="fk-field-label">Type</div>
          <div class="fk-field-value">
            <span class="fk-badge fk-badge-<?= $booking['type']==='hotel'?'info':'secondary' ?>">
              <?= $booking['type']==='hotel' ? '🏨 Hôtel' : '🏙️ Ville' ?>
            </span>
          </div>
        </div>
        <div>
          <div class="fk-field-label">Lieu</div>
          <div class="fk-field-value">
            <?php if ($booking['type'] === 'hotel'): ?>
              <?= sanitize($booking['hotel_name'] ?? '–') ?>
              <?php if (!empty($booking['room_number'])): ?> · Chambre <?= sanitize($booking['room_number']) ?><?php endif; ?>
            <?php else: ?>
              <?= sanitize($booking['city'] ?? '') ?>
              <?php if (!empty($booking['area'])): ?> · <?= sanitize($booking['area']) ?><?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <div class="fk-field-label">Date</div>
          <div class="fk-field-value"><?= sanitize(format_date_fr($booking['service_date'])) ?></div>
        </div>
        <div>
          <div class="fk-field-label">Heure de début</div>
          <div class="fk-field-value"><?= sanitize($booking['start_time']) ?></div>
        </div>
        <div>
          <div class="fk-field-label">Durée</div>
          <div class="fk-field-value"><?= format_duration($booking['duration_minutes'] ?? 0) ?></div>
        </div>
        <div>
          <div class="fk-field-label">Nombre d'enfants</div>
          <div class="fk-field-value"><?= (int)$booking['children_count'] ?></div>
        </div>
        <?php if ($booking['type'] === 'city' && !empty($booking['address'])): ?>
        <div style="grid-column:span 2">
          <div class="fk-field-label">Adresse</div>
          <div class="fk-field-value"><?= sanitize($booking['address']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($booking['special_requests'])): ?>
        <div style="grid-column:span 2">
          <div class="fk-field-label">Demandes spéciales</div>
          <div class="fk-field-value" style="white-space:pre-line"><?= sanitize($booking['special_requests']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Children -->
  <?php if ($children): ?>
  <div class="fk-card" style="margin-bottom:20px">
    <div class="fk-card-header">
      <h3 class="fk-card-title">👶 Enfants (<?= count($children) ?>)</h3>
    </div>
    <div class="fk-card-body" style="padding:0">
      <table class="fk-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Prénom</th>
            <th>Âge</th>
            <th>Allergies / Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($children as $i => $child): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= sanitize($child['first_name'] ?? '–') ?></td>
            <td><?= sanitize($child['age'] ?? '–') ?> ans</td>
            <td><?= sanitize($child['allergies'] ?? '–') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- WhatsApp History -->
  <div class="fk-card" style="margin-bottom:20px">
    <div class="fk-card-header">
      <h3 class="fk-card-title">💬 Historique WhatsApp</h3>
      <button class="fk-btn fk-btn-ghost fk-btn-sm" onclick="openWAModal(<?= $booking_id ?>)">+ Nouveau message</button>
    </div>
    <?php if ($history): ?>
    <div class="fk-card-body" style="padding:0">
      <table class="fk-table">
        <thead>
          <tr><th>Date</th><th>Template</th><th>Aperçu</th><th>Admin</th></tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
          <tr>
            <td style="white-space:nowrap"><?= sanitize(format_date_fr($h['sent_at'])) ?></td>
            <td><span class="fk-badge fk-badge-secondary"><?= sanitize($h['template_key'] ?? 'Manuel') ?></span></td>
            <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sanitize(truncate($h['message_text'] ?? '', 80)) ?></td>
            <td><?= sanitize($h['admin_name'] ?? 'Système') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="fk-empty-state" style="padding:32px">
      <div class="fk-empty-icon">💬</div>
      <div class="fk-empty-text">Aucun message envoyé</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Activity Log -->
  <div class="fk-card">
    <div class="fk-card-header">
      <h3 class="fk-card-title">📜 Journal d'activité</h3>
    </div>
    <?php if ($activity): ?>
    <div class="fk-card-body">
      <div class="fk-timeline">
        <?php foreach ($activity as $act): ?>
        <div class="fk-timeline-item">
          <div class="fk-timeline-dot"></div>
          <div class="fk-timeline-content">
            <div class="fk-timeline-title"><?= sanitize($act['action']) ?></div>
            <?php if (!empty($act['details'])): ?>
            <div class="fk-timeline-sub"><?= sanitize($act['details']) ?></div>
            <?php endif; ?>
            <div class="fk-timeline-time"><?= sanitize(time_ago($act['created_at'])) ?> · <?= sanitize($act['username'] ?? 'Système') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="fk-empty-state" style="padding:32px">
      <div class="fk-empty-icon">📜</div>
      <div class="fk-empty-text">Aucune activité enregistrée</div>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /left -->

<!-- RIGHT COLUMN -->
<div>

  <!-- Status Actions -->
  <div class="fk-card" style="margin-bottom:16px">
    <div class="fk-card-header"><h3 class="fk-card-title">⚡ Actions</h3></div>
    <div class="fk-card-body">
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php if ($booking['status'] === 'pending'): ?>
        <button class="fk-btn fk-btn-success fk-btn-block" onclick="setStatus(<?= $booking_id ?>, 'confirmed')">✓ Confirmer la réservation</button>
        <button class="fk-btn fk-btn-danger fk-btn-block" onclick="setStatus(<?= $booking_id ?>, 'cancelled')">✕ Annuler la réservation</button>
        <?php elseif ($booking['status'] === 'confirmed'): ?>
        <button class="fk-btn fk-btn-info fk-btn-block" onclick="setStatus(<?= $booking_id ?>, 'in_progress')">▶ Marquer en cours</button>
        <button class="fk-btn fk-btn-danger fk-btn-block" onclick="setStatus(<?= $booking_id ?>, 'cancelled')">✕ Annuler</button>
        <?php elseif ($booking['status'] === 'in_progress'): ?>
        <button class="fk-btn fk-btn-success fk-btn-block" onclick="setStatus(<?= $booking_id ?>, 'completed')">✓ Marquer terminée</button>
        <?php elseif ($booking['status'] === 'completed'): ?>
        <div class="fk-badge fk-badge-success" style="display:block;text-align:center;padding:10px">✓ Prestation terminée</div>
        <?php elseif ($booking['status'] === 'cancelled'): ?>
        <div class="fk-badge fk-badge-danger" style="display:block;text-align:center;padding:10px">Réservation annulée</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Price -->
  <div class="fk-card" style="margin-bottom:16px">
    <div class="fk-card-header"><h3 class="fk-card-title">💰 Tarification</h3></div>
    <div class="fk-card-body">
      <?php if (!empty($booking['price_amount'])): ?>
      <div style="font-size:28px;font-weight:700;color:#059669;text-align:center;margin-bottom:8px"><?= format_price($booking['price_amount']) ?></div>
      <div style="font-size:12px;color:#6B7A72;text-align:center;margin-bottom:16px">
        <?= sanitize($payment_labels[$booking['payment_status']] ?? '–') ?>
      </div>
      <?php else: ?>
      <div style="font-size:13px;color:#6B7A72;text-align:center;margin-bottom:16px">Tarif non défini</div>
      <?php endif; ?>
      <form id="priceForm" onsubmit="savePrice(event)">
        <div class="fk-field" style="margin-bottom:10px">
          <label class="fk-label">Montant (DH)</label>
          <input type="number" class="fk-input" name="price_amount" value="<?= (float)($booking['price_amount'] ?? 0) ?>" min="0" step="0.01">
        </div>
        <div class="fk-field" style="margin-bottom:10px">
          <label class="fk-label">Statut paiement</label>
          <select class="fk-select" name="payment_status">
            <?php foreach ($payment_labels as $val => $lab): ?>
            <option value="<?= $val ?>" <?= $booking['payment_status']==$val?'selected':'' ?>><?= sanitize($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="fk-btn fk-btn-primary fk-btn-block">Enregistrer le tarif</button>
      </form>
    </div>
  </div>

  <!-- Babysitter -->
  <div class="fk-card" style="margin-bottom:16px">
    <div class="fk-card-header"><h3 class="fk-card-title">👩 Babysitter</h3></div>
    <div class="fk-card-body">
      <?php if ($booking['babysitter_name']): ?>
      <div style="font-size:15px;font-weight:600;color:#1A2E24;margin-bottom:4px"><?= sanitize($booking['babysitter_name']) ?></div>
      <?php if ($booking['babysitter_phone']): ?>
      <a href="<?= htmlspecialchars(get_whatsapp_link($booking['babysitter_phone'], 'Bonjour, concernant votre mission du ' . $booking['service_date'])) ?>" target="_blank" class="fk-link-green" style="font-size:13px"><?= sanitize($booking['babysitter_phone']) ?></a>
      <?php endif; ?>
      <div style="margin-top:12px"></div>
      <?php endif; ?>
      <form id="babysitterForm" onsubmit="saveBabysitter(event)">
        <div class="fk-field" style="margin-bottom:10px">
          <select class="fk-select" name="babysitter_id">
            <option value="">– Aucune assignée –</option>
            <?php foreach ($babysitters as $bs): ?>
            <option value="<?= $bs['id'] ?>" <?= $booking['babysitter_id']==$bs['id']?'selected':'' ?>><?= sanitize($bs['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="fk-btn fk-btn-primary fk-btn-block">Assigner</button>
      </form>
    </div>
  </div>

  <!-- Payment Proof -->
  <?php if ($proofs): ?>
  <div class="fk-card" style="margin-bottom:16px">
    <div class="fk-card-header"><h3 class="fk-card-title">🧾 Preuves de paiement (<?= count($proofs) ?>)</h3></div>
    <div class="fk-card-body">
      <?php foreach ($proofs as $proof): ?>
      <div style="border:1px solid #E5EDE9;border-radius:8px;padding:12px;margin-bottom:10px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:13px;font-weight:600;color:#1A2E24"><?= sanitize($proof['original_name'] ?? $proof['filename']) ?></span>
          <a href="<?= htmlspecialchars($proof['file_url']) ?>" target="_blank" class="fk-btn fk-btn-ghost fk-btn-sm">Voir</a>
        </div>
        <div style="font-size:12px;color:#6B7A72"><?= sanitize(format_date_fr($proof['created_at'])) ?></div>
        <?php if (!empty($proof['notes'])): ?>
        <div style="font-size:12px;color:#6B7A72;margin-top:4px"><?= sanitize($proof['notes']) ?></div>
        <?php endif; ?>
        <?php if (($booking['payment_status'] ?? '') !== 'validated'): ?>
        <div style="display:flex;gap:6px;margin-top:8px">
          <button class="fk-btn fk-btn-success fk-btn-sm" onclick="validateProof(<?= $proof['id'] ?>, <?= $booking_id ?>)">✓ Valider</button>
          <button class="fk-btn fk-btn-danger fk-btn-sm" onclick="refuseProof(<?= $proof['id'] ?>, <?= $booking_id ?>)">✕ Refuser</button>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Request Payment -->
  <?php if (in_array($booking['payment_status'] ?? '', ['pending', 'not_required'])): ?>
  <div class="fk-card" style="margin-bottom:16px">
    <div class="fk-card-header"><h3 class="fk-card-title">📤 Demander le paiement</h3></div>
    <div class="fk-card-body">
      <p style="font-size:13px;color:#6B7A72;margin-bottom:12px">Envoie un lien de paiement par WhatsApp au client.</p>
      <button class="fk-btn fk-btn-wa fk-btn-block" onclick="requestPayment(<?= $booking_id ?>)">
        Envoyer le lien de paiement
      </button>
    </div>
  </div>
  <?php endif; ?>

  <!-- Notes -->
  <div class="fk-card">
    <div class="fk-card-header"><h3 class="fk-card-title">📝 Notes internes</h3></div>
    <div class="fk-card-body">
      <form id="notesForm" onsubmit="saveNotes(event)">
        <textarea class="fk-input" name="notes" rows="5" style="resize:vertical;font-family:inherit"
          placeholder="Notes visibles uniquement par l'équipe..."><?= sanitize($booking['notes'] ?? '') ?></textarea>
        <button type="submit" class="fk-btn fk-btn-primary fk-btn-block" style="margin-top:10px">Enregistrer</button>
      </form>
    </div>
  </div>

</div><!-- /right -->

</div><!-- /grid -->

<?php
$extra_js = <<<JS
<script>
const BOOKING_ID = {$booking_id};

async function setStatus(id, status) {
    const labels = {confirmed:'Confirmer',cancelled:'Annuler',in_progress:'Marquer en cours',completed:'Marquer terminée'};
    if (!confirm('Voulez-vous ' + (labels[status]||status) + ' cette réservation ?')) return;
    const r = await FK.post('/api/bookings?action=status', {id, status});
    if (r.success) { FK.toast('Statut mis à jour','success'); setTimeout(()=>location.reload(),800); }
    else FK.toast(r.error||'Erreur','error');
}

async function savePrice(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const r = await FK.post('/api/bookings?action=set_price', {
        id: BOOKING_ID,
        price_amount: fd.get('price_amount'),
        payment_status: fd.get('payment_status')
    });
    if (r.success) FK.toast('Tarif enregistré','success');
    else FK.toast(r.error||'Erreur','error');
}

async function saveBabysitter(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const r = await FK.post('/api/bookings?action=assign_babysitter', {
        id: BOOKING_ID,
        babysitter_id: fd.get('babysitter_id') || null
    });
    if (r.success) { FK.toast('Babysitter assignée','success'); setTimeout(()=>location.reload(),800); }
    else FK.toast(r.error||'Erreur','error');
}

async function saveNotes(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const r = await FK.post('/api/bookings?action=update', {id: BOOKING_ID, notes: fd.get('notes')});
    if (r.success) FK.toast('Notes enregistrées','success');
    else FK.toast(r.error||'Erreur','error');
}

async function validateProof(proofId, bookingId) {
    if (!confirm('Valider cette preuve de paiement ?')) return;
    const r = await FK.post('/api/payments?action=validate', {proof_id: proofId, booking_id: bookingId});
    if (r.success) { FK.toast('Preuve validée','success'); setTimeout(()=>location.reload(),800); }
    else FK.toast(r.error||'Erreur','error');
}

async function refuseProof(proofId, bookingId) {
    if (!confirm('Refuser cette preuve de paiement ?')) return;
    const r = await FK.post('/api/payments?action=refuse', {proof_id: proofId, booking_id: bookingId});
    if (r.success) { FK.toast('Preuve refusée','warning'); setTimeout(()=>location.reload(),800); }
    else FK.toast(r.error||'Erreur','error');
}

async function requestPayment(bookingId) {
    const r = await FK.post('/api/payments?action=request', {booking_id: bookingId});
    if (r.success) { FK.toast('Demande envoyée','success'); if (r.wa_link) window.open(r.wa_link,'_blank'); }
    else FK.toast(r.error||'Erreur','error');
}
</script>
JS;
include __DIR__ . '/layout-bottom.php';
?>
