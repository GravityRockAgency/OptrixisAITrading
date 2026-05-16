<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$page_title    = 'Disponibilités';
$page_subtitle = 'Bloquer des jours, des créneaux ou des périodes chargées.';

// Handle POST: add block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    db_insert('availability_blocks', [
        'type'       => $_POST['type'] ?? 'full_day',
        'date_start' => $_POST['date_start'] ?? date('Y-m-d'),
        'date_end'   => !empty($_POST['date_end'])   ? $_POST['date_end']   : null,
        'time_start' => !empty($_POST['time_start'])  ? $_POST['time_start'] : null,
        'time_end'   => !empty($_POST['time_end'])    ? $_POST['time_end']   : null,
        'status'     => $_POST['status']   ?? 'blocked',
        'scope'      => $_POST['scope']    ?? 'global',
        'hotel_id'   => !empty($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : null,
        'note'       => trim($_POST['note'] ?? ''),
        'created_by' => get_admin()['id'] ?? null,
    ]);
    log_activity('Blocage disponibilité ajouté', $_POST['note'] ?? '');
    header('Location: /admin/availability?success=1');
    exit;
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $bid = (int)($_POST['block_id'] ?? 0);
    if ($bid) db_delete('availability_blocks', 'id', $bid);
    header('Location: /admin/availability');
    exit;
}

$blocks = db_fetch_all(
    "SELECT ab.*, h.name as hotel_name FROM availability_blocks ab
     LEFT JOIN hotels h ON ab.hotel_id = h.id
     ORDER BY ab.date_start DESC",
    []
);
$hotels = db_fetch_all("SELECT id, name FROM hotels WHERE is_active=1 ORDER BY sort_order", []);

$status_labels = ['available'=>'Disponible','limited'=>'Limité','blocked'=>'Bloqué','unavailable'=>'Indisponible'];
$type_labels   = ['full_day'=>'Journée complète','time_slot'=>'Créneau horaire','busy_period'=>'Période chargée'];
$scope_labels  = ['global'=>'Global','hotel'=>'Hôtel spécifique','city'=>'City'];
$status_colors = ['available'=>'#22C55E','limited'=>'#F59E0B','blocked'=>'#EF4444','unavailable'=>'#6B7A72'];

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <button class="fk-btn fk-btn-primary" onclick="document.getElementById('addBlockModal').style.display='flex'">+ Ajouter un blocage</button>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="fk-alert fk-alert-success" style="margin-bottom:16px">Blocage ajouté avec succès.</div>
<?php endif; ?>

<div class="fk-card">
  <div class="fk-card-header">
    <h3 class="fk-section-title">Règles de disponibilité</h3>
    <span class="fk-badge"><?= count($blocks) ?> règle(s)</span>
  </div>
  <?php if (empty($blocks)): ?>
  <div class="fk-empty-state">
    <div style="font-size:48px;margin-bottom:16px">📅</div>
    <div class="fk-empty-title">Aucun blocage défini</div>
    <div class="fk-empty-text">Ajoutez des règles pour bloquer des jours ou créneaux.</div>
  </div>
  <?php else: ?>
  <div class="fk-table-wrapper">
    <table class="fk-table">
      <thead><tr>
        <th>Date début</th><th>Date fin</th><th>Type</th><th>Heure</th>
        <th>Statut</th><th>Scope</th><th>Hôtel / City</th><th>Note</th><th>Action</th>
      </tr></thead>
      <tbody>
      <?php foreach ($blocks as $b): ?>
      <tr>
        <td><?= sanitize($b['date_start']) ?></td>
        <td><?= $b['date_end'] ? sanitize($b['date_end']) : '—' ?></td>
        <td><span class="fk-badge"><?= sanitize($type_labels[$b['type']] ?? $b['type']) ?></span></td>
        <td style="font-size:13px;color:#6B7A72">
          <?= $b['time_start'] ? substr($b['time_start'],0,5) . ($b['time_end'] ? ' – '.substr($b['time_end'],0,5) : '') : '—' ?>
        </td>
        <td>
          <span class="fk-badge" style="background:<?= htmlspecialchars($status_colors[$b['status']]??'#6B7A72') ?>20;color:<?= htmlspecialchars($status_colors[$b['status']]??'#6B7A72') ?>">
            <?= sanitize($status_labels[$b['status']] ?? $b['status']) ?>
          </span>
        </td>
        <td><?= sanitize($scope_labels[$b['scope']] ?? $b['scope']) ?></td>
        <td><?= $b['hotel_name'] ? sanitize($b['hotel_name']) : ($b['scope']==='city'?'City':'—') ?></td>
        <td style="font-size:13px;color:#6B7A72"><?= sanitize(truncate($b['note'] ?? '', 40)) ?></td>
        <td>
          <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce blocage ?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="block_id" value="<?= $b['id'] ?>">
            <button type="submit" class="fk-btn fk-btn-danger fk-btn-sm">Supprimer</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Add block modal -->
<div id="addBlockModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;max-width:520px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 style="font-size:16px;font-weight:700;color:#1A2E24">Ajouter un blocage</h3>
      <button onclick="document.getElementById('addBlockModal').style.display='none'" style="border:none;background:none;font-size:20px;cursor:pointer;color:#6B7A72">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div style="padding:20px 24px;display:grid;gap:14px">
        <div>
          <label class="fk-label">Type de blocage</label>
          <div style="display:flex;gap:8px;margin-top:8px">
            <?php foreach ($type_labels as $val => $lbl): ?>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
              <input type="radio" name="type" value="<?= $val ?>" <?= $val==='full_day'?'checked':'' ?> onchange="toggleTimeFields(this.value)">
              <?= $lbl ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div><label class="fk-label">Date début</label>
            <input type="date" name="date_start" class="fk-input" value="<?= date('Y-m-d') ?>" required></div>
          <div><label class="fk-label">Date fin</label>
            <input type="date" name="date_end" class="fk-input"></div>
        </div>
        <div id="time-fields" style="display:none;grid-template-columns:1fr 1fr;gap:12px">
          <div><label class="fk-label">Heure début</label>
            <input type="time" name="time_start" class="fk-input"></div>
          <div><label class="fk-label">Heure fin</label>
            <input type="time" name="time_end" class="fk-input"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div><label class="fk-label">Statut</label>
            <select name="status" class="fk-select">
              <?php foreach ($status_labels as $val=>$lbl): ?>
              <option value="<?= $val ?>" <?= $val==='blocked'?'selected':'' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select></div>
          <div><label class="fk-label">Portée</label>
            <select name="scope" class="fk-select" onchange="toggleHotelField(this.value)">
              <?php foreach ($scope_labels as $val=>$lbl): ?>
              <option value="<?= $val ?>"><?= $lbl ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <div id="hotel-select" style="display:none">
          <label class="fk-label">Hôtel</label>
          <select name="hotel_id" class="fk-select">
            <option value="">— Sélectionner —</option>
            <?php foreach ($hotels as $h): ?>
            <option value="<?= $h['id'] ?>"><?= sanitize($h['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label class="fk-label">Note / Raison</label>
          <input type="text" name="note" class="fk-input" placeholder="Ex: Journée fériée, congé équipe..."></div>
      </div>
      <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('addBlockModal').style.display='none'" class="fk-btn fk-btn-secondary">Annuler</button>
        <button type="submit" class="fk-btn fk-btn-primary">Enregistrer le blocage</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleTimeFields(type) {
  const tf = document.getElementById('time-fields');
  tf.style.display = type === 'time_slot' ? 'grid' : 'none';
}
function toggleHotelField(scope) {
  document.getElementById('hotel-select').style.display = scope === 'hotel' ? 'block' : 'none';
}
</script>

<?php include 'layout-bottom.php'; ?>
