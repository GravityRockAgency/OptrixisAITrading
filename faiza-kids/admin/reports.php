<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$page_title    = 'Rapports';
$page_subtitle = 'Statistiques, revenus, sources et performance opérationnelle.';

$date_start = $_GET['date_start'] ?? date('Y-m-01');
$date_end   = $_GET['date_end']   ?? date('Y-m-d');
$period     = $_GET['period']     ?? 'month';

$total_bookings = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE service_date BETWEEN ? AND ?", [$date_start, $date_end])['c'] ?? 0;
$hotel_bookings = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE type='hotel' AND service_date BETWEEN ? AND ?", [$date_start, $date_end])['c'] ?? 0;
$city_bookings  = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE type='city' AND service_date BETWEEN ? AND ?", [$date_start, $date_end])['c'] ?? 0;
$total_revenue  = db_fetch("SELECT COALESCE(SUM(final_price),0) as t FROM bookings WHERE status IN ('confirmed','completed') AND service_date BETWEEN ? AND ? AND final_price IS NOT NULL", [$date_start, $date_end])['t'] ?? 0;
$cancelled      = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE status='cancelled' AND service_date BETWEEN ? AND ?", [$date_start, $date_end])['c'] ?? 0;
$no_show        = db_fetch("SELECT COUNT(*) as c FROM bookings WHERE status='no_show' AND service_date BETWEEN ? AND ?", [$date_start, $date_end])['c'] ?? 0;
$avg_duration   = db_fetch("SELECT COALESCE(AVG(duration_minutes),0) as a FROM bookings WHERE service_date BETWEEN ? AND ? AND status NOT IN ('cancelled','no_show')", [$date_start, $date_end])['a'] ?? 0;
$avg_price      = db_fetch("SELECT COALESCE(AVG(final_price),0) as a FROM bookings WHERE final_price IS NOT NULL AND service_date BETWEEN ? AND ?", [$date_start, $date_end])['a'] ?? 0;
$cancel_rate    = $total_bookings > 0 ? round(($cancelled / $total_bookings) * 100, 1) : 0;

$hotel_revenue = db_fetch_all(
    "SELECT h.name, h.code, h.primary_color, COALESCE(SUM(b.final_price),0) as revenue, COUNT(b.id) as cnt
     FROM hotels h LEFT JOIN bookings b ON b.hotel_id=h.id AND b.service_date BETWEEN ? AND ? AND b.status IN ('confirmed','completed') AND b.final_price IS NOT NULL
     GROUP BY h.id ORDER BY revenue DESC",
    [$date_start, $date_end]
);

$sources = db_fetch_all(
    "SELECT source, COUNT(*) as cnt FROM bookings WHERE service_date BETWEEN ? AND ? GROUP BY source ORDER BY cnt DESC",
    [$date_start, $date_end]
);

// 6-month trend
$monthly_labels = [];
$monthly_values = [];
for ($i = 5; $i >= 0; $i--) {
    $ms = date('Y-m-01', strtotime("-$i months"));
    $me = date('Y-m-t', strtotime("-$i months"));
    $rev = db_fetch("SELECT COALESCE(SUM(final_price),0) as t FROM bookings WHERE status IN ('confirmed','completed') AND service_date BETWEEN ? AND ? AND final_price IS NOT NULL", [$ms, $me])['t'] ?? 0;
    $monthly_labels[] = date('M Y', strtotime($ms));
    $monthly_values[] = (float)$rev;
}

$source_labels_fr = [
    'direct'          => 'Direct',
    'manual'          => 'Manuel',
    'whatsapp'        => 'WhatsApp',
    'hotel_reception' => 'Réception hôtel',
    'qr_hotel'        => 'QR Code hôtel',
    'nfc_hotel'       => 'NFC hôtel',
    'city_link'       => 'Lien City',
    'instagram'       => 'Instagram',
];

$extra_js = '<script>
document.addEventListener("DOMContentLoaded", function() {
  if(typeof Chart !== "undefined") {
    const mctx = document.getElementById("monthlyChart");
    if(mctx) new Chart(mctx, {
      type:"bar",
      data:{
        labels:'.json_encode($monthly_labels).',
        datasets:[{label:"Revenu (DH)",data:'.json_encode($monthly_values).',backgroundColor:"#2D6A4F",borderRadius:6,borderSkipped:false}]
      },
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>ctx.raw+" DH"}}},
        scales:{y:{beginAtZero:true,grid:{color:"#F0F5F2"}},x:{grid:{display:false}}}}
    });
  }
});
</script>';

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="/api/export?type=bookings&date_start=<?= urlencode($date_start) ?>&date_end=<?= urlencode($date_end) ?>" class="fk-btn fk-btn-secondary">⬇ CSV</a>
    <a href="/api/reports?action=export_pdf&date_start=<?= urlencode($date_start) ?>&date_end=<?= urlencode($date_end) ?>" target="_blank" class="fk-btn fk-btn-secondary">🖨 PDF</a>
  </div>
</div>

<!-- Date filter -->
<div class="fk-card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <div style="display:flex;gap:6px">
      <?php foreach (['7days'=>'7 jours','30days'=>'30 jours','month'=>'Ce mois','custom'=>'Personnalisée'] as $p=>$l): ?>
      <a href="?period=<?= $p ?>" class="fk-btn fk-btn-sm <?= $period===$p?'fk-btn-primary':'fk-btn-secondary' ?>"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end">
      <div><label class="fk-label" style="font-size:11px">Du</label>
        <input type="date" name="date_start" class="fk-input" style="padding:8px 10px" value="<?= sanitize($date_start) ?>"></div>
      <div><label class="fk-label" style="font-size:11px">Au</label>
        <input type="date" name="date_end" class="fk-input" style="padding:8px 10px" value="<?= sanitize($date_end) ?>"></div>
      <input type="hidden" name="period" value="custom">
      <button type="submit" class="fk-btn fk-btn-primary fk-btn-sm">Appliquer</button>
    </div>
  </form>
</div>

<!-- KPI row -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#1A2E24"><?= $total_bookings ?></div>
    <div class="fk-kpi-label">Total réservations</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#2D6A4F"><?= format_price($total_revenue) ?></div>
    <div class="fk-kpi-label">Revenu total</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#EF4444"><?= $cancel_rate ?>%</div>
    <div class="fk-kpi-label">Taux d'annulation</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#0EA5E9"><?= format_duration((int)$avg_duration) ?></div>
    <div class="fk-kpi-label">Durée moyenne</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#2D6A4F"><?= $hotel_bookings ?></div>
    <div class="fk-kpi-label">Réservations hôtel</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#5B21B6"><?= $city_bookings ?></div>
    <div class="fk-kpi-label">Réservations City</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:28px;font-weight:700;color:#F59E0B"><?= $cancelled + $no_show ?></div>
    <div class="fk-kpi-label">Annulations / No-show</div>
  </div>
  <div class="fk-kpi-card">
    <div style="font-size:22px;font-weight:700;color:#2D6A4F"><?= format_price($avg_price) ?></div>
    <div class="fk-kpi-label">Ticket moyen</div>
  </div>
</div>

<!-- Charts -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">
  <div class="fk-card">
    <div class="fk-card-header"><h3 class="fk-section-title">Évolution des revenus (6 mois)</h3></div>
    <?php if (array_sum($monthly_values) > 0): ?>
    <div style="height:220px"><canvas id="monthlyChart"></canvas></div>
    <?php else: ?>
    <div class="fk-empty-state" style="padding:30px">
      <div style="font-size:32px;margin-bottom:8px">📊</div>
      <div class="fk-empty-title">Aucune donnée de revenu</div>
    </div>
    <?php endif; ?>
  </div>

  <div class="fk-card">
    <div class="fk-card-header"><h3 class="fk-section-title">Sources des réservations</h3></div>
    <?php if (empty($sources)): ?>
    <div class="fk-empty-state" style="padding:20px">Aucune donnée.</div>
    <?php else:
      $source_total = array_sum(array_column($sources,'cnt'));
      foreach ($sources as $src):
        $pct = $source_total > 0 ? round($src['cnt']/$source_total*100) : 0;
        $src_lbl = $source_labels_fr[$src['source']] ?? ucfirst($src['source']);
    ?>
    <div style="margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
        <span style="color:#1A2E24"><?= sanitize($src_lbl) ?></span>
        <span style="color:#6B7A72"><?= $src['cnt'] ?> (<?= $pct ?>%)</span>
      </div>
      <div style="height:6px;background:#F0F5F2;border-radius:3px">
        <div style="height:6px;width:<?= $pct ?>%;background:#2D6A4F;border-radius:3px"></div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Revenue per hotel -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">Revenu par hôtel / canal</h3></div>
  <?php if (empty($hotel_revenue)): ?>
  <div class="fk-empty-state" style="padding:20px">Aucune donnée de revenu.</div>
  <?php else:
    $max_rev = max(array_column($hotel_revenue,'revenue') ?: [1]);
    foreach ($hotel_revenue as $hr):
      $pct = $max_rev > 0 ? round($hr['revenue']/$max_rev*100) : 0;
      $color = htmlspecialchars($hr['primary_color'] ?? '#2D6A4F');
  ?>
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <div style="width:36px;height:36px;border-radius:8px;background:<?= $color ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
      <?= htmlspecialchars(substr($hr['code']??'H',0,3)) ?>
    </div>
    <div style="flex:1">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
        <span style="font-weight:500;color:#1A2E24"><?= sanitize($hr['name']) ?></span>
        <span style="font-weight:700;color:#2D6A4F"><?= format_price($hr['revenue']) ?></span>
      </div>
      <div style="height:8px;background:#F0F5F2;border-radius:4px">
        <div style="height:8px;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:4px;transition:.3s"></div>
      </div>
      <div style="font-size:11px;color:#9CA3AF;margin-top:2px"><?= $hr['cnt'] ?> réservation(s)</div>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<?php include 'layout-bottom.php'; ?>
