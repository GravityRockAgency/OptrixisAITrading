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

$i18n = [
    'fr' => [
        'title'       => 'Demande envoyée !',
        'sub'         => 'Votre demande de babysitting a bien été reçue.',
        'ref'         => 'Référence',
        'copy'        => 'Copier',
        'copied'      => 'Copié ✓',
        'steps_title' => 'Prochaines étapes',
        'step1'       => 'Notre équipe vérifie votre demande et la disponibilité.',
        'step2'       => 'Vous serez contacté(e) via WhatsApp pour confirmer les détails et le tarif.',
        'step3'       => 'La réservation est confirmée après accord sur le tarif et, si nécessaire, réception du paiement.',
        'warn'        => '⚠️ Cette demande ne constitue pas encore une réservation confirmée.',
        'wa_btn'      => 'Contacter notre équipe sur WhatsApp',
        'back'        => 'Retour à l\'accueil',
    ],
    'en' => [
        'title'       => 'Request sent!',
        'sub'         => 'Your babysitting request has been received.',
        'ref'         => 'Reference',
        'copy'        => 'Copy',
        'copied'      => 'Copied ✓',
        'steps_title' => 'Next steps',
        'step1'       => 'Our team will review your request and check availability.',
        'step2'       => 'You will be contacted via WhatsApp to confirm details and pricing.',
        'step3'       => 'The booking is confirmed after agreement on price and, if required, payment receipt.',
        'warn'        => '⚠️ This request is not yet a confirmed booking.',
        'wa_btn'      => 'Contact our team on WhatsApp',
        'back'        => 'Back to home',
    ],
    'ar' => [
        'title'       => 'تم إرسال الطلب!',
        'sub'         => 'تم استلام طلب الجليسة الخاص بكم.',
        'ref'         => 'المرجع',
        'copy'        => 'نسخ',
        'copied'      => 'تم النسخ ✓',
        'steps_title' => 'الخطوات التالية',
        'step1'       => 'سيراجع فريقنا طلبكم ويتحقق من التوفر.',
        'step2'       => 'سيتواصل معكم عبر واتساب لتأكيد التفاصيل والسعر.',
        'step3'       => 'يتم تأكيد الحجز بعد الاتفاق على السعر واستلام الدفع إذا لزم.',
        'warn'        => '⚠️ هذا الطلب لا يُعدّ حجزاً مؤكداً بعد.',
        'wa_btn'      => 'تواصل مع فريقنا على واتساب',
        'back'        => 'العودة للرئيسية',
    ],
];

$t = $i18n[$lang] ?? $i18n['fr'];

$wa_msgs = [
    'fr' => "Bonjour, j'ai soumis une demande de babysitting (Réf. $reference). Pouvez-vous me confirmer la disponibilité ?",
    'en' => "Hello, I submitted a babysitting request (Ref. $reference). Can you confirm availability?",
    'ar' => "مرحباً، لقد أرسلت طلب جليسة أطفال (المرجع: $reference). هل يمكنكم تأكيد التوفر؟",
];
$wa_link = get_whatsapp_link($admin_wa, $wa_msgs[$lang] ?? $wa_msgs['fr']);

$dir      = $lang === 'ar' ? 'rtl' : 'ltr';
$base_url = defined('BASE_URL') ? BASE_URL : '';
$co_logo  = get_setting('company_logo', '');
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
:root{--hotel-color:#014D3E;--hotel-color-end:#005B45;}
.pk-success-wrap{max-width:540px;width:92%;margin:48px auto 64px;}
.pk-success-icon{width:76px;height:76px;border-radius:50%;background:#D1FAE5;color:#059669;font-size:36px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:pkPop .4s cubic-bezier(.2,1.6,.4,1);}
@keyframes pkPop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.pk-success-title{font-size:24px;font-weight:700;color:#101827;margin-bottom:6px;text-align:center;}
.pk-success-sub{font-size:15px;color:#667085;margin-bottom:24px;text-align:center;}
.pk-ref-box{background:#F0FAF5;border:1px solid #A7F3D0;border-radius:12px;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:28px;}
.pk-ref-label{font-size:11px;color:#667085;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;}
.pk-ref-value{font-size:20px;font-weight:700;color:#065F46;font-family:monospace;letter-spacing:.05em;}
.pk-ref-copy{border:1px solid #A7F3D0;background:#fff;color:#065F46;border-radius:6px;padding:5px 12px;cursor:pointer;font-size:12px;font-weight:600;}
.pk-steps-card{background:#fff;border:1px solid #E2E8E5;border-radius:16px;padding:24px;margin-bottom:16px;}
.pk-steps-title{font-size:13px;font-weight:700;color:#101827;margin-bottom:16px;text-transform:uppercase;letter-spacing:.05em;}
.pk-step-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;}
.pk-step-item:last-child{margin-bottom:0;}
.pk-step-num{width:24px;height:24px;border-radius:50%;background:#014D3E;color:#52B788;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.pk-step-text{font-size:14px;color:#374151;line-height:1.55;}
.pk-warn-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;font-size:13px;color:#92400E;margin-bottom:20px;}
.pk-wa-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px 24px;background:#25D366;color:#fff;border-radius:12px;text-decoration:none;font-size:15px;font-weight:600;margin-bottom:12px;transition:background .2s;}
.pk-wa-btn:hover{background:#1DA851;}
.pk-back-link{display:block;text-align:center;font-size:14px;color:#667085;text-decoration:none;margin-top:4px;}
.pk-back-link:hover{color:#014D3E;}
</style>
</head>
<body class="pk-page">

<header class="pk-header">
  <div class="pk-header-inner">
    <div class="pk-brand">
      <?php if ($co_logo): ?>
        <img src="<?= htmlspecialchars($co_logo) ?>" alt="Faiza Kids" class="pk-brand-logo-img">
      <?php else: ?>
        <div class="pk-brand-badge">FK</div>
      <?php endif; ?>
      <div class="pk-brand-info">
        <div class="pk-brand-name">Faiza Kids Concierge</div>
      </div>
    </div>
  </div>
</header>

<main class="pk-main">
  <div class="pk-success-wrap">

    <div class="pk-success-icon">✓</div>
    <h1 class="pk-success-title"><?= sanitize($t['title']) ?></h1>
    <p class="pk-success-sub"><?= sanitize($t['sub']) ?></p>

    <?php if ($reference): ?>
    <div class="pk-ref-box">
      <div>
        <div class="pk-ref-label"><?= sanitize($t['ref']) ?></div>
        <div class="pk-ref-value"><?= sanitize($reference) ?></div>
      </div>
      <button class="pk-ref-copy" onclick="window.copyRef('<?= htmlspecialchars($reference) ?>', this)" data-label="<?= sanitize($t['copy']) ?>" data-copied="<?= sanitize($t['copied']) ?>"><?= sanitize($t['copy']) ?></button>
    </div>
    <?php endif; ?>

    <div class="pk-steps-card">
      <div class="pk-steps-title">📋 <?= sanitize($t['steps_title']) ?></div>
      <?php foreach ([1=>$t['step1'],2=>$t['step2'],3=>$t['step3']] as $n=>$step): ?>
      <div class="pk-step-item">
        <div class="pk-step-num"><?= $n ?></div>
        <div class="pk-step-text"><?= sanitize($step) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="pk-warn-box"><?= sanitize($t['warn']) ?></div>

    <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank" rel="noopener" class="pk-wa-btn">
      💬 <?= sanitize($t['wa_btn']) ?>
    </a>

    <?php
    $back_url = $base_url . '/';
    if ($booking && $booking['type'] === 'hotel' && !empty($booking['hotel_id'])) {
        $h = db_fetch("SELECT slug FROM hotels WHERE id=?", [$booking['hotel_id']]);
        if ($h) $back_url = $base_url . '/hotel/' . $h['slug'];
    } elseif ($booking && $booking['type'] === 'city') {
        $back_url = $base_url . '/city';
    }
    ?>
    <a href="<?= htmlspecialchars($back_url) ?>" class="pk-back-link">← <?= sanitize($t['back']) ?></a>

  </div>
</main>

<footer class="pk-footer">
  <p>© <?= date('Y') ?> Faiza Multiservice · Agadir, Maroc</p>
</footer>

<script src="<?= $base_url ?>/assets/js/public.js"></script>
</body>
</html>
