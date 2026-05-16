<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page_title    = 'QR / NFC';
$page_subtitle = 'Liens directs pour QR codes et tags NFC.';

$qr_links = db_fetch_all(
    "SELECT ql.*, h.name as hotel_name, h.primary_color as hotel_color
     FROM qr_links ql
     LEFT JOIN hotels h ON ql.hotel_id = h.id
     ORDER BY ql.id ASC",
    []
);

$base_url = defined('BASE_URL') ? BASE_URL : get_setting('base_url', '');

// Update URLs in case BASE_URL changed
foreach ($qr_links as &$ql) {
    if ($ql['type'] === 'city') {
        $ql['url'] = $base_url . '/city?source=' . ($ql['source_param'] ?? 'qr-city');
    } elseif (!empty($ql['hotel_id'])) {
        $hotel = db_fetch("SELECT slug FROM hotels WHERE id=?", [$ql['hotel_id']]);
        if ($hotel) {
            $ql['url'] = $base_url . '/hotel/' . $hotel['slug'] . '?source=' . ($ql['source_param'] ?? 'qr');
        }
    }
}
unset($ql);

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:20px">
<?php foreach ($qr_links as $ql):
  $color   = htmlspecialchars($ql['hotel_color'] ?? '#2D6A4F');
  $qr_url  = 'https://chart.googleapis.com/chart?cht=qr&chs=280x280&chl=' . urlencode($ql['url']) . '&choe=UTF-8&chld=M|2';
  $qr_name = sanitize($ql['name']);
?>
<div class="fk-card" style="border-top:4px solid <?= $color ?>">
  <div style="text-align:center;margin-bottom:16px">
    <div style="font-size:16px;font-weight:700;color:#1A2E24;margin-bottom:4px"><?= $qr_name ?></div>
    <span class="fk-badge <?= $ql['type']==='hotel'?'fk-badge-hotel':'fk-badge-city' ?>"><?= $ql['type']==='hotel'?'Hôtel':'City' ?></span>
  </div>

  <div style="text-align:center;margin-bottom:14px">
    <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR <?= $qr_name ?>"
         style="width:160px;height:160px;border:1px solid #E5EDE9;border-radius:8px;padding:4px"
         onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'160\' height=\'160\' viewBox=\'0 0 160 160\'><rect width=\'160\' height=\'160\' fill=\'%23F8FAF9\'/><text x=\'80\' y=\'85\' text-anchor=\'middle\' fill=\'%236B7A72\' font-size=\'12\'>QR non disponible</text></svg>'">
  </div>

  <div style="background:#F8FAF9;border-radius:8px;padding:8px 10px;margin-bottom:12px;font-size:11px;color:#6B7A72;word-break:break-all">
    <?= htmlspecialchars($ql['url']) ?>
  </div>

  <div style="display:flex;justify-content:center;gap:12px;font-size:12px;color:#6B7A72;margin-bottom:14px">
    <span>📊 <?= $ql['scan_count'] ?? 0 ?> scans</span>
    <span>✅ <?= $ql['conversion_count'] ?? 0 ?> conversions</span>
  </div>

  <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center">
    <button onclick="navigator.clipboard.writeText(<?= json_encode($ql['url']) ?>).then(()=>showCopyMsg(this))"
            class="fk-btn fk-btn-secondary fk-btn-sm">📋 Copier URL</button>
    <a href="<?= htmlspecialchars($ql['url']) ?>" target="_blank" class="fk-btn fk-btn-secondary fk-btn-sm">🔗 Ouvrir</a>
    <a href="<?= htmlspecialchars($qr_url.'&chs=600x600') ?>" download="qr-<?= urlencode($ql['source_param']??'qr') ?>.png"
       class="fk-btn fk-btn-secondary fk-btn-sm">⬇ PNG</a>
    <button onclick="printQR(<?= json_encode($ql['url']) ?>, <?= json_encode($ql['name']) ?>)"
            class="fk-btn fk-btn-secondary fk-btn-sm">🖨 Imprimer</button>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- NFC section -->
<div class="fk-card" style="border:2px dashed #E5EDE9">
  <div style="display:flex;align-items:center;gap:16px">
    <div style="width:48px;height:48px;border-radius:12px;background:#F0FAF5;display:flex;align-items:center;justify-content:center;font-size:24px">📡</div>
    <div>
      <div style="font-size:15px;font-weight:700;color:#1A2E24;margin-bottom:4px">Tags NFC</div>
      <div style="font-size:13px;color:#6B7A72">La programmation des tags NFC sera disponible prochainement. Les mêmes URLs que les QR codes peuvent être utilisées pour programmer vos tags NFC manuellement.</div>
    </div>
    <span class="fk-badge fk-badge-info" style="white-space:nowrap;flex-shrink:0">Bientôt</span>
  </div>
  <div style="margin-top:16px;padding-top:16px;border-top:1px solid #E5EDE9">
    <div style="font-size:13px;font-weight:600;color:#1A2E24;margin-bottom:10px">URLs pour tags NFC :</div>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php foreach ($qr_links as $ql): ?>
      <div style="display:flex;align-items:center;gap:10px;background:#F8FAF9;border-radius:8px;padding:8px 12px">
        <span style="font-size:13px;font-weight:500;color:#1A2E24;flex:0 0 180px"><?= sanitize($ql['name']) ?></span>
        <span style="font-size:12px;color:#6B7A72;font-family:monospace;flex:1;word-break:break-all"><?= htmlspecialchars($ql['url']) ?></span>
        <button onclick="navigator.clipboard.writeText(<?= json_encode($ql['url']) ?>).then(()=>showCopyMsg(this))"
                class="fk-btn fk-btn-secondary fk-btn-sm" style="flex-shrink:0">Copier</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div id="copyToast" style="display:none;position:fixed;bottom:24px;right:24px;background:#0D2B1D;color:#fff;padding:12px 20px;border-radius:10px;font-size:14px;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,0.2)">
  ✓ Copié dans le presse-papier
</div>

<script>
function showCopyMsg(btn) {
  const toast = document.getElementById('copyToast');
  toast.style.display = 'block';
  setTimeout(()=>toast.style.display='none', 2000);
}

function printQR(url, name) {
  const win = window.open('', '_blank');
  const qrSrc = 'https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=' + encodeURIComponent(url) + '&choe=UTF-8&chld=M|2';
  win.document.write(`<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>QR - ${name}</title>
<style>
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;text-align:center;padding:60px 40px;color:#1A2E24}
  .logo{width:50px;height:50px;border-radius:50%;background:#0D2B1D;color:#52B788;font-size:22px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
  .brand{font-size:14px;font-weight:600;color:#6B7A72;margin-bottom:24px}
  img{display:block;margin:0 auto 20px;border:1px solid #E5EDE9;border-radius:8px;padding:8px}
  h2{font-size:20px;margin-bottom:8px}
  p{color:#6B7A72;font-size:13px;margin:4px 0}
  .url{font-size:11px;color:#9CA3AF;font-family:monospace;word-break:break-all;max-width:340px;margin:8px auto}
  @media print{body{padding:20px}}
</style></head>
<body>
  <div class="logo">F</div>
  <div class="brand">Faiza Kids Concierge</div>
  <img src="${qrSrc}" width="240" height="240" alt="QR Code">
  <h2>${name}</h2>
  <p>Scannez ce code pour réserver votre babysitter</p>
  <div class="url">${url}</div>
  <script>window.onload=()=>window.print();<\/script>
</body></html>`);
}
</script>

<?php include 'layout-bottom.php'; ?>
