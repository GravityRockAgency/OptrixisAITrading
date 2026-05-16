<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page_title    = 'Calendrier';
$page_subtitle = 'Vue opérationnelle des réservations hôtel et City.';

$week_offset = (int)($_GET['week'] ?? 0);
$week_start  = date('Y-m-d', strtotime('monday this week') + ($week_offset * 7 * 86400));
$week_end    = date('Y-m-d', strtotime($week_start) + 6 * 86400);
$today       = date('Y-m-d');

$bookings = db_fetch_all(
    "SELECT b.*, h.name as hotel_name, h.code as hotel_code, h.primary_color as hotel_color,
     bs.full_name as babysitter_name
     FROM bookings b
     LEFT JOIN hotels h ON b.hotel_id = h.id
     LEFT JOIN babysitters bs ON b.babysitter_id = bs.id
     WHERE b.service_date BETWEEN ? AND ? AND b.status NOT IN ('cancelled','no_show')
     ORDER BY b.service_date ASC, b.start_time ASC",
    [$week_start, $week_end]
);

// Group bookings by date
$bookings_by_date = [];
foreach ($bookings as $bk) {
    $bookings_by_date[$bk['service_date']][] = $bk;
}

// Today stats
$today_stats = [
    'total'    => db_fetch("SELECT COUNT(*) as c FROM bookings WHERE service_date=? AND status NOT IN ('cancelled','no_show')", [$today])['c'] ?? 0,
    'children' => db_fetch("SELECT COALESCE(SUM(children_count),0) as c FROM bookings WHERE service_date=? AND status NOT IN ('cancelled','no_show')", [$today])['c'] ?? 0,
    'hours'    => db_fetch("SELECT COALESCE(SUM(duration_minutes),0) as c FROM bookings WHERE service_date=? AND status NOT IN ('cancelled','no_show')", [$today])['c'] ?? 0,
    'pending'  => db_fetch("SELECT COUNT(*) as c FROM bookings WHERE service_date=? AND status IN ('new','pending')", [$today])['c'] ?? 0,
    'city_q'   => db_fetch("SELECT COUNT(*) as c FROM bookings WHERE service_date=? AND type='city' AND price_status='pending'", [$today])['c'] ?? 0,
];

$days_of_week = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime($week_start) + $i * 86400);
    $days_of_week[] = $d;
}

$fr_days = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];

// Build JSON for JS calendar
$cal_data = [];
foreach ($bookings as $bk) {
    $cal_data[] = [
        'id'           => $bk['id'],
        'reference'    => $bk['reference'],
        'client_name'  => $bk['client_name'],
        'client_wa'    => $bk['client_whatsapp'],
        'type'         => $bk['type'],
        'hotel_name'   => $bk['hotel_name'] ?? ($bk['city'] ?: 'City'),
        'hotel_color'  => $bk['hotel_color'] ?? '#2D6A4F',
        'date'         => $bk['service_date'],
        'start_time'   => substr($bk['start_time'], 0, 5),
        'duration'     => $bk['duration_minutes'],
        'children'     => $bk['children_count'],
        'babysitter'   => $bk['babysitter_name'] ?? '—',
        'status'       => $bk['status'],
        'room'         => $bk['room_number'] ?? $bk['area'] ?? '',
    ];
}

$extra_js = '<script>
const CAL_BOOKINGS = ' . json_encode($cal_data) . ';
const CAL_WEEK_START = "' . $week_start . '";
</script>';

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="/admin/bookings" class="fk-btn fk-btn-secondary">Toutes les réservations</a>
    <a href="/admin/manual-booking" class="fk-btn fk-btn-primary">+ Nouvelle réservation</a>
  </div>
</div>

<!-- Week navigation -->
<div class="fk-card" style="margin-bottom:16px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:8px">
      <a href="?week=<?= $week_offset - 1 ?>" class="fk-btn fk-btn-secondary fk-btn-sm">← Sem. préc.</a>
      <a href="?week=0" class="fk-btn fk-btn-secondary fk-btn-sm">Aujourd'hui</a>
      <a href="?week=<?= $week_offset + 1 ?>" class="fk-btn fk-btn-secondary fk-btn-sm">Sem. suiv. →</a>
    </div>
    <div style="font-weight:600;color:#1A2E24">
      Semaine du <?= date('d/m', strtotime($week_start)) ?> au <?= date('d/m/Y', strtotime($week_end)) ?>
    </div>
    <div style="display:flex;gap:4px">
      <span class="fk-badge" style="background:#D8F3DC;color:#2D6A4F">Hôtel</span>
      <span class="fk-badge" style="background:#EDE9FE;color:#5B21B6">City</span>
      <span class="fk-badge fk-badge-success">Confirmée</span>
      <span class="fk-badge fk-badge-warning">En attente</span>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:16px;align-items:start">

<!-- Calendar Grid -->
<div class="fk-card" style="padding:0;overflow:hidden">
  <!-- Day headers -->
  <div style="display:grid;grid-template-columns:52px repeat(7,1fr);border-bottom:2px solid #E5EDE9">
    <div style="padding:10px;background:#F8FAF9;border-right:1px solid #E5EDE9"></div>
    <?php foreach ($days_of_week as $d):
      $dow = (int)date('w', strtotime($d));
      $is_today = $d === $today;
      $day_name = $fr_days[$dow];
      $day_num  = date('d', strtotime($d));
    ?>
    <div style="padding:10px 8px;text-align:center;background:<?= $is_today ? '#F0FAF5' : '#F8FAF9' ?>;border-right:1px solid #E5EDE9;<?= $is_today?'font-weight:700;color:#2D6A4F':'' ?>">
      <div style="font-size:11px;color:#6B7A72;text-transform:uppercase"><?= $day_name ?></div>
      <div style="font-size:18px;font-weight:600;color:<?= $is_today?'#2D6A4F':'#1A2E24' ?>"><?= $day_num ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Time slots -->
  <div style="position:relative;overflow-y:auto;max-height:620px">
    <?php for ($hour = 8; $hour <= 22; $hour++): ?>
    <div style="display:grid;grid-template-columns:52px repeat(7,1fr);min-height:52px;<?= $hour < 22 ? 'border-bottom:1px solid #F0F5F2' : '' ?>">
      <div style="padding:6px 8px;font-size:11px;color:#9CA3AF;text-align:right;border-right:1px solid #E5EDE9;padding-top:8px"><?= sprintf('%02d:00', $hour) ?></div>
      <?php foreach ($days_of_week as $d):
        $day_bookings = array_filter($bookings_by_date[$d] ?? [], function($bk) use ($hour) {
          $bk_hour = (int)date('H', strtotime('1970-01-01 ' . $bk['start_time']));
          return $bk_hour === $hour;
        });
        $is_today = $d === $today;
      ?>
      <div style="position:relative;border-right:1px solid #E5EDE9;background:<?= $is_today?'rgba(45,106,79,0.03)':'transparent' ?>;padding:2px">
        <?php foreach ($day_bookings as $bk):
          $color = $bk['type'] === 'hotel' ? ($bk['hotel_color'] ?? '#2D6A4F') : '#5B21B6';
          $bg    = $bk['status'] === 'confirmed' ? $color : '#F59E0B';
          $mins  = min($bk['duration_minutes'], 52);
        ?>
        <div onclick="openBookingModal(<?= $bk['id'] ?>)"
             style="background:<?= htmlspecialchars($bg) ?>;color:#fff;border-radius:6px;padding:4px 6px;font-size:11px;cursor:pointer;margin-bottom:2px;line-height:1.3;overflow:hidden"
             title="<?= sanitize($bk['client_name']) ?> — <?= sanitize($bk['reference']) ?>">
          <strong><?= substr($bk['start_time'],0,5) ?></strong>
          <?= sanitize(truncate($bk['client_name'],12)) ?>
          <br><span style="opacity:.85"><?= sanitize(truncate($bk['hotel_name'] ?? $bk['city'] ?? 'City', 12)) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endfor; ?>
  </div>
</div>

<!-- Right panel: Focus du jour -->
<div style="display:flex;flex-direction:column;gap:12px">
  <div class="fk-card">
    <h3 class="fk-section-title" style="margin-bottom:16px">Focus du jour</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div style="background:#F0FAF5;border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:24px;font-weight:700;color:#2D6A4F"><?= $today_stats['total'] ?></div>
        <div style="font-size:11px;color:#6B7A72;margin-top:4px">Réservations</div>
      </div>
      <div style="background:#F0F9FF;border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:24px;font-weight:700;color:#0369A1"><?= $today_stats['children'] ?></div>
        <div style="font-size:11px;color:#6B7A72;margin-top:4px">Enfants</div>
      </div>
      <div style="background:#FFFBEB;border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:24px;font-weight:700;color:#92400E"><?= $today_stats['pending'] ?></div>
        <div style="font-size:11px;color:#6B7A72;margin-top:4px">À confirmer</div>
      </div>
      <div style="background:#FAF5FF;border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:24px;font-weight:700;color:#5B21B6"><?= $today_stats['city_q'] ?></div>
        <div style="font-size:11px;color:#6B7A72;margin-top:4px">Devis City</div>
      </div>
    </div>
    <?php if ($today_stats['total'] === 0): ?>
    <div style="text-align:center;padding:20px 0;color:#6B7A72;font-size:13px">
      Aucune réservation prévue aujourd'hui.
    </div>
    <?php endif; ?>
  </div>

  <div class="fk-card">
    <h3 class="fk-section-title" style="margin-bottom:12px">Légende</h3>
    <div style="display:flex;flex-direction:column;gap:8px;font-size:13px">
      <div style="display:flex;align-items:center;gap:8px"><span style="width:14px;height:14px;border-radius:3px;background:#2D6A4F;display:inline-block"></span>Hôtel confirmé</div>
      <div style="display:flex;align-items:center;gap:8px"><span style="width:14px;height:14px;border-radius:3px;background:#5B21B6;display:inline-block"></span>City confirmé</div>
      <div style="display:flex;align-items:center;gap:8px"><span style="width:14px;height:14px;border-radius:3px;background:#F59E0B;display:inline-block"></span>En attente</div>
    </div>
  </div>

  <a href="/admin/manual-booking" class="fk-btn fk-btn-primary" style="width:100%;text-align:center">+ Nouvelle réservation</a>
  <a href="/admin/availability" class="fk-btn fk-btn-secondary" style="width:100%;text-align:center">Gérer les disponibilités</a>
</div>
</div>

<!-- Booking detail modal -->
<div id="bookingModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;display:none;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;max-width:560px;width:90%;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="padding:20px 24px;border-bottom:1px solid #E5EDE9;display:flex;justify-content:space-between;align-items:center">
      <h3 style="font-size:16px;font-weight:700;color:#1A2E24">Détail réservation</h3>
      <button onclick="closeModal()" style="border:none;background:none;cursor:pointer;font-size:20px;color:#6B7A72">×</button>
    </div>
    <div id="bookingModalContent" style="padding:24px">Chargement...</div>
    <div style="padding:16px 24px;border-top:1px solid #E5EDE9;display:flex;gap:8px;justify-content:flex-end">
      <button id="modal-wa-btn" class="fk-btn fk-btn-secondary fk-btn-sm" style="background:#25D366;color:#fff;border:none">💬 WhatsApp</button>
      <a id="modal-view-btn" href="#" class="fk-btn fk-btn-primary fk-btn-sm">Voir réservation</a>
      <button onclick="closeModal()" class="fk-btn fk-btn-secondary fk-btn-sm">Fermer</button>
    </div>
  </div>
</div>

<script>
function openBookingModal(bookingId) {
  const modal = document.getElementById('bookingModal');
  modal.style.display = 'flex';
  document.getElementById('modal-view-btn').href = '/admin/bookings/view/' + bookingId;

  const bk = CAL_BOOKINGS.find(b => b.id === bookingId);
  if (!bk) return;

  const statusLabels = {new:'Nouvelle',pending:'En attente',confirmed:'Confirmée',in_progress:'En cours',completed:'Terminée'};
  const typeLabel = bk.type === 'hotel' ? '🏨 Hôtel' : '🏙️ City';
  const waPhone = bk.client_wa.replace(/[^\d]/g,'');

  document.getElementById('bookingModalContent').innerHTML = `
    <div style="display:grid;gap:12px">
      <div style="display:flex;gap:10px;align-items:center">
        <span style="background:#0D2B1D;color:#52B788;font-family:monospace;padding:4px 10px;border-radius:6px;font-weight:700">${bk.reference}</span>
        <span style="background:#F3F4F6;color:#374151;padding:3px 10px;border-radius:99px;font-size:12px">${statusLabels[bk.status]||bk.status}</span>
        <span style="background:#D8F3DC;color:#2D6A4F;padding:3px 10px;border-radius:99px;font-size:12px">${typeLabel}</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:14px">
        <div><span style="color:#6B7A72">Client</span><br><strong>${bk.client_name}</strong></div>
        <div><span style="color:#6B7A72">WhatsApp</span><br><strong>${bk.client_wa}</strong></div>
        <div><span style="color:#6B7A72">Lieu</span><br><strong>${bk.hotel_name}</strong>${bk.room?' — Chambre '+bk.room:''}</div>
        <div><span style="color:#6B7A72">Date</span><br><strong>${bk.date} à ${bk.start_time}</strong></div>
        <div><span style="color:#6B7A72">Durée</span><br><strong>${Math.floor(bk.duration/60)}h${bk.duration%60||''}</strong></div>
        <div><span style="color:#6B7A72">Enfants</span><br><strong>${bk.children} enfant(s)</strong></div>
        <div><span style="color:#6B7A72">Babysitter</span><br><strong>${bk.babysitter}</strong></div>
      </div>
    </div>`;

  document.getElementById('modal-wa-btn').onclick = function() {
    window.open('https://wa.me/' + waPhone, '_blank');
  };
}

function closeModal() {
  document.getElementById('bookingModal').style.display = 'none';
}

document.getElementById('bookingModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

<?php include 'layout-bottom.php'; ?>
