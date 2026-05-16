<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$reference = $_GET['reference'] ?? '';
$lang      = $_GET['lang']      ?? 'fr';
$hotel_name = $_GET['hotel']    ?? '';

if (!in_array($lang, ['fr','en','ar'])) $lang = 'fr';

$booking = null;
if ($reference) {
    $booking = db_fetch(
        "SELECT b.*, h.name as hotel_name FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.id WHERE b.reference=?",
        [$reference]
    );
}

$admin_wa = get_setting('admin_whatsapp', '+212600000000');
$wa_phone = preg_replace('/[^\d]/', '', $admin_wa);

$t = [
    'fr' => [
        'title'   => 'Demande envoyée !',
        'sub'     => 'Votre demande de babysitting a bien été reçue.',
        'ref'     => 'Référence',
        'steps_title' => 'Prochaines étapes',
        'step1'   => 'Notre équipe vérifie votre demande et la disponibilité.',
        'step2'   => 'Vous serez contacté(e) via WhatsApp pour confirmer les détails et le tarif.',
        'step3'   => 'La réservation est confirmée après accord sur le tarif et, si nécessaire, réception du paiement.',
        'warn'    => '⚠️ Cette demande ne constitue pas encore une réservation confirmée.',
        'wa_btn'  => 'Contacter notre équipe sur WhatsApp',
        'back'    => 'Retour à l\'accueil',
    ],
    'en' => [
        'title'   => 'Request sent!',
        'sub'     => 'Your babysitting request has been received.',
        'ref'     => 'Reference',
        'steps_title' => 'Next steps',
        'step1'   => 'Our team will review your request and check availability.',
        'step2'   => 'You will be contacted via WhatsApp to confirm details and pricing.',
        'step3'   => 'The booking is confirmed after agreement on price and, if required, payment receipt.',
        'warn'    => '⚠️ This request is not yet a confirmed booking.',
        'wa_btn'  => 'Contact our team on WhatsApp',
        'back'    => 'Back to home',
    ],
    'ar' => [
        'title'   => 'تم إرسال الطلب!',
        'sub'     => 'تم استلام طلب الجليسة الخاص بكم.',
        'ref'     => 'المرجع',
        'steps_title' => 'الخطوات التالية',
        'step1'   => 'سيراجع فريقنا طلبكم ويتحقق من التوفر.',
        'step2'   => 'سيتواصل معكم عبر واتساب لتأكيد التفاصيل والسعر.',
        'step3'   => 'يتم تأكيد الحجز بعد الاتفاق على السعر واستلام الدفع إذا لزم.',
        'warn'    => '⚠️ هذا الطلب لا يُعدّ حجزاً مؤكداً بعد.',
        'wa_btn'  => 'تواصل مع فريقنا على واتساب',
        'back'    => 'العودة للرئيسية',
    ],
][$lang] ?? [];

$wa_msg_fr = "Bonjour, j'ai soumis une demande de babysitting (Réf. $reference). Pouvez-vous me confirmer la disponibilité ?";
$wa_msg_en = "Hello, I submitted a babysitting request (Ref. $reference). Can you confirm availability?";
$wa_msg_ar = "مرحباً، لقد أرسلت طلب جليسة أطفال (المرجع: $reference). هل يمكنكم تأكيد التوفر؟";
$wa_msgs = ['fr'=>$wa_msg_fr,'en'=>$wa_msg_en,'ar'=>$wa_msg_ar];
$wa_link = get_whatsapp_link($admin_wa, $wa_msgs[$lang] ?? $wa_msg_fr);

$dir      = $lang === 'ar' ? 'rtl' : 'ltr';
$base_url = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($t['title']) ?> – Faiza Kids</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/public.css">
<style>
body{font-family:'Inter',sans-serif;background:#F8FAF9;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:0}
.success-wrap{max-width:520px;width:90%;margin:60px auto;text-align:center}
.success-icon{width:80px;height:80px;border-radius:50%;background:#D1FAE5;color:#059669;font-size:40px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;animation:popIn .4s cubic-bezier(.2,1.6,.4,1)}
@keyframes popIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.success-title{font-size:24px;font-weight:700;color:#1A2E24;margin-bottom:8px}
.success-sub{font-size:16px;color:#6B7A72;margin-bottom:24px}
.ref-box{background:#F0FAF5;border:1px solid #A7F3D0;border-radius:12px;padding:14px 20px;display:inline-flex;align-items:center;gap:12px;margin-bottom:28px}
.ref-label{font-size:12px;color:#6B7A72;font-weight:600;text-transform:uppercase}
.ref-value{font-size:18px;font-weight:700;color:#065F46;font-family:monospace}
.steps-card{background:#fff;border:1px solid #E5EDE9;border-radius:16px;padding:24px;text-align:<?= $dir==='rtl'?'right':'left' ?>;margin-bottom:20px}
.steps-title{font-size:14px;font-weight:700;color:#1A2E24;margin-bottom:16px}
.step-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px}
.step-num{width:24px;height:24px;border-radius:50%;background:#0D2B1D;color:#52B788;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.step-text{font-size:14px;color:#374151;line-height:1.5}
.warn-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;font-size:13px;color:#92400E;margin-bottom:24px;text-align:<?= $dir==='rtl'?'right':'left' ?>}
.wa-btn{display:block;padding:14px 24px;background:#25D366;color:#fff;border-radius:12px;text-decoration:none;font-size:15px;font-weight:600;margin-bottom:12px;transition:.2s}
.wa-btn:hover{background:#1DA851}
.back-link{font-size:14px;color:#6B7A72;text-decoration:none}
.back-link:hover{color:#2D6A4F}
.fk-logo-area{margin-bottom:32px}
.fk-logo-circle{width:48px;height:48px;border-radius:50%;background:#0D2B1D;color:#52B788;font-size:20px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 8px}
.fk-logo-name{font-size:14px;font-weight:700;color:#1A2E24}
</style>
</head>
<body>
<div class="success-wrap">
  <div class="fk-logo-area">
    <div class="fk-logo-circle">F</div>
    <div class="fk-logo-name">Faiza Kids Concierge</div>
  </div>

  <div class="success-icon">✓</div>
  <h1 class="success-title"><?= sanitize($t['title']) ?></h1>
  <p class="success-sub"><?= sanitize($t['sub']) ?></p>

  <?php if ($reference): ?>
  <div class="ref-box">
    <div>
      <div class="ref-label"><?= sanitize($t['ref']) ?></div>
      <div class="ref-value"><?= sanitize($reference) ?></div>
    </div>
    <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($reference) ?>').then(()=>this.textContent='✓')" style="border:1px solid #A7F3D0;background:#F0FAF5;color:#065F46;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:12px">Copier</button>
  </div>
  <?php endif; ?>

  <div class="steps-card">
    <div class="steps-title">📋 <?= sanitize($t['steps_title']) ?></div>
    <?php foreach ([1=>$t['step1'],2=>$t['step2'],3=>$t['step3']] as $n=>$step): ?>
    <div class="step-item">
      <div class="step-num"><?= $n ?></div>
      <div class="step-text"><?= sanitize($step) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="warn-box"><?= sanitize($t['warn']) ?></div>

  <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank" class="wa-btn">
    💬 <?= sanitize($t['wa_btn']) ?>
  </a>

  <?php
  $hotel_slug = $booking['hotel_id'] ?? null;
  $back_url = $base_url . '/';
  if ($booking && $booking['type'] === 'hotel' && !empty($hotel_slug)) {
      $h = db_fetch("SELECT slug FROM hotels WHERE id=?", [$booking['hotel_id']]);
      if ($h) $back_url = $base_url . '/hotel/' . $h['slug'];
  } elseif ($booking && $booking['type'] === 'city') {
      $back_url = $base_url . '/city';
  }
  ?>
  <a href="<?= htmlspecialchars($back_url) ?>" class="back-link">← <?= sanitize($t['back']) ?></a>
</div>
</body>
</html>
