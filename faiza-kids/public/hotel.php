<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$slug  = $_GET['slug'] ?? '';
$hotel = get_hotel_by_slug($slug);
if (!$hotel) {
    http_response_code(404);
    die('Hôtel introuvable.');
}

// Track QR source
$source = $_GET['source'] ?? 'direct';
if (!empty($source) && str_starts_with($source, 'qr-')) {
    try { db_query("UPDATE qr_links SET scan_count=scan_count+1 WHERE source_param=?", [$source]); } catch(Exception $e){}
}

$lang = $_GET['lang'] ?? $_POST['client_language'] ?? $_COOKIE['fk_lang'] ?? 'fr';
if (!in_array($lang, ['fr','en','ar'])) $lang = 'fr';

$error   = '';
$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name     = trim($_POST['client_name']    ?? '');
    $client_whatsapp = trim($_POST['client_whatsapp'] ?? '');
    $client_email    = trim($_POST['client_email']   ?? '');
    $client_language = $_POST['client_language']     ?? $lang;
    $room_number     = trim($_POST['room_number']    ?? '');
    $service_date    = $_POST['service_date']        ?? '';
    $start_time      = $_POST['start_time']          ?? '';
    $duration_mins   = (int)($_POST['duration_minutes'] ?? 120);
    $children_count  = max(1,(int)($_POST['children_count']  ?? 1));
    $special_needs   = trim($_POST['special_needs']  ?? '');
    $lang            = $client_language;

    if (!$client_name)     $errors[] = 'Votre nom est obligatoire.';
    if (!$client_whatsapp) $errors[] = 'Votre numéro WhatsApp est obligatoire.';
    if (!$service_date)    $errors[] = 'La date du service est obligatoire.';
    if (!$start_time)      $errors[] = 'L\'heure est obligatoire.';

    // Check 24h minimum
    if ($service_date && $start_time) {
        $booking_ts  = strtotime($service_date . ' ' . $start_time);
        $min_hours   = (int)get_setting('min_booking_hours', '24');
        if ($booking_ts < (time() + $min_hours * 3600)) {
            $errors[] = "La demande doit être effectuée au moins $min_hours heures avant le service.";
        }
    }

    if (empty($errors)) {
        $end_ts   = strtotime($service_date . ' ' . $start_time) + ($duration_mins * 60);
        $end_time = date('H:i:s', $end_ts);
        $reference = generate_reference();
        $token = generate_secure_token(20);

        $booking_id = db_insert('bookings', [
            'reference'        => $reference,
            'type'             => 'hotel',
            'source'           => $source,
            'hotel_id'         => $hotel['id'],
            'client_name'      => $client_name,
            'client_whatsapp'  => $client_whatsapp,
            'client_email'     => $client_email,
            'client_language'  => $client_language,
            'room_number'      => $room_number,
            'service_date'     => $service_date,
            'start_time'       => $start_time,
            'duration_minutes' => $duration_mins,
            'end_time'         => $end_time,
            'children_count'   => $children_count,
            'special_needs'    => $special_needs,
            'status'           => 'new',
            'payment_status'   => 'pending',
            'secure_token'     => $token,
        ]);

        // Insert children
        $child_names = $_POST['child_name']      ?? [];
        $child_ages  = $_POST['child_age']       ?? [];
        $child_allrg = $_POST['child_allergies'] ?? [];
        $child_needs = $_POST['child_needs']     ?? [];
        for ($i = 0; $i < $children_count; $i++) {
            if (!empty($child_names[$i]) || !empty($child_ages[$i])) {
                db_insert('booking_children', [
                    'booking_id'   => $booking_id,
                    'child_name'   => $child_names[$i] ?? '',
                    'age'          => !empty($child_ages[$i]) ? (int)$child_ages[$i] : null,
                    'allergies'    => $child_allrg[$i] ?? '',
                    'special_needs'=> $child_needs[$i] ?? '',
                    'sort_order'   => $i,
                ]);
            }
        }

        // Notification admin
        try {
            db_insert('notifications', [
                'type'       => 'new_booking',
                'title'      => 'Nouvelle demande hôtel',
                'message'    => "$client_name — {$hotel['name']} — $service_date",
                'booking_id' => $booking_id,
            ]);
        } catch(Exception $e){}

        // Track QR conversion
        if (str_starts_with($source, 'qr-')) {
            try { db_query("UPDATE qr_links SET conversion_count=conversion_count+1 WHERE source_param=?", [$source]); } catch(Exception $e){}
        }

        header("Location: " . (defined('BASE_URL')?BASE_URL:'') . "/success/$reference?lang=$lang&hotel=" . urlencode($hotel['name']));
        exit;
    }
}

$primary_color = $hotel['primary_color'] ?? '#2D6A4F';
$accent_color  = $hotel['accent_color']  ?? '#E8C342';

$i18n = [
    'fr' => [
        'title'          => $hotel['public_title'] ?? 'Réservez une babysitter de confiance',
        'subtitle'       => $hotel['public_text']  ?? 'Profitez de votre séjour pendant que Faiza Multiservice prend soin de vos enfants.',
        'form_title'     => 'Demande de babysitting',
        'name'           => 'Nom complet',
        'whatsapp'       => 'Numéro WhatsApp / Téléphone',
        'email'          => 'Email (optionnel)',
        'room'           => 'Numéro de chambre',
        'date'           => 'Date du service',
        'time'           => 'Heure de début',
        'duration'       => 'Durée',
        'children'       => 'Nombre d\'enfants',
        'child_name'     => 'Prénom',
        'child_age'      => 'Âge',
        'child_allergies'=> 'Allergies',
        'child_needs'    => 'Besoins particuliers',
        'notes'          => 'Notes complémentaires',
        'notice'         => 'Votre demande sera étudiée rapidement par notre équipe. Le tarif final vous sera communiqué via WhatsApp après vérification de la disponibilité, de l\'âge des enfants et des besoins spécifiques.',
        'rules_title'    => 'Règles du service',
        'rule1'          => 'Demande au moins 24 heures avant l\'heure souhaitée.',
        'rule2'          => 'Annulation au moins 3 heures avant le service.',
        'rule3'          => 'Annulation tardive : remboursement limité à 50%.',
        'submit'         => 'Envoyer la demande',
        'trust1'         => 'Intervenantes sélectionnées',
        'trust2'         => 'Coordination avec l\'hôtel',
        'trust3'         => 'Support WhatsApp',
        'trust4'         => 'Service multilingue',
    ],
    'en' => [
        'title'          => 'Book a trusted babysitter',
        'subtitle'       => 'Enjoy your stay while Faiza Multiservice takes care of your children.',
        'form_title'     => 'Babysitting request',
        'name'           => 'Full name',
        'whatsapp'       => 'WhatsApp / Phone number',
        'email'          => 'Email (optional)',
        'room'           => 'Room number',
        'date'           => 'Service date',
        'time'           => 'Start time',
        'duration'       => 'Duration',
        'children'       => 'Number of children',
        'child_name'     => 'First name',
        'child_age'      => 'Age',
        'child_allergies'=> 'Allergies',
        'child_needs'    => 'Special needs',
        'notes'          => 'Additional notes',
        'notice'         => 'Your request will be reviewed quickly by our team. The final price will be communicated via WhatsApp after verifying availability, children\'s ages, and specific needs.',
        'rules_title'    => 'Service rules',
        'rule1'          => 'Request at least 24 hours before the desired time.',
        'rule2'          => 'Cancellation at least 3 hours before the service.',
        'rule3'          => 'Late cancellation: refund limited to 50%.',
        'submit'         => 'Send request',
        'trust1'         => 'Vetted babysitters',
        'trust2'         => 'Hotel coordination',
        'trust3'         => 'WhatsApp support',
        'trust4'         => 'Multilingual service',
    ],
    'ar' => [
        'title'          => 'احجزي جليسة أطفال موثوقة',
        'subtitle'       => 'استمتعي بإقامتكم بينما تعتني فيزا ملتي سيرفيس بأطفالكم.',
        'form_title'     => 'طلب خدمة جليسة الأطفال',
        'name'           => 'الاسم الكامل',
        'whatsapp'       => 'رقم واتساب / الهاتف',
        'email'          => 'البريد الإلكتروني (اختياري)',
        'room'           => 'رقم الغرفة',
        'date'           => 'تاريخ الخدمة',
        'time'           => 'وقت البداية',
        'duration'       => 'المدة',
        'children'       => 'عدد الأطفال',
        'child_name'     => 'الاسم',
        'child_age'      => 'العمر',
        'child_allergies'=> 'الحساسية',
        'child_needs'    => 'احتياجات خاصة',
        'notes'          => 'ملاحظات إضافية',
        'notice'         => 'سيتم مراجعة طلبكم بسرعة من قِبل فريقنا. سيتم إبلاغكم بالسعر النهائي عبر واتساب بعد التحقق من التوفر وأعمار الأطفال والاحتياجات الخاصة.',
        'rules_title'    => 'قواعد الخدمة',
        'rule1'          => 'الطلب قبل 24 ساعة على الأقل من الوقت المطلوب.',
        'rule2'          => 'الإلغاء قبل 3 ساعات على الأقل من الخدمة.',
        'rule3'          => 'الإلغاء المتأخر: استرداد محدود بـ 50%.',
        'submit'         => 'إرسال الطلب',
        'trust1'         => 'جليسات مختارة',
        'trust2'         => 'تنسيق مع الفندق',
        'trust3'         => 'دعم واتساب',
        'trust4'         => 'خدمة متعددة اللغات',
    ],
];

$t   = $i18n[$lang] ?? $i18n['fr'];
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$base_url = defined('BASE_URL') ? BASE_URL : '';
$min_date = date('Y-m-d', time() + 86400);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($hotel['name']) ?> – Faiza Kids Concierge</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/public.css">
<meta name="hotel-color" content="<?= htmlspecialchars($primary_color) ?>">
<style>
:root { --hotel-color: <?= htmlspecialchars($primary_color) ?>; --hotel-accent: <?= htmlspecialchars($accent_color) ?>; }
</style>
</head>
<body class="fk-public-page">

<!-- Header -->
<header class="fk-public-header">
  <div class="fk-public-header-inner">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:36px;height:36px;border-radius:50%;background:#0D2B1D;color:#52B788;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0">F</div>
      <div>
        <div style="font-size:13px;font-weight:700;color:#1A2E24">Faiza Kids Concierge</div>
        <div style="font-size:11px;color:#6B7A72"><?= sanitize($hotel['name']) ?></div>
      </div>
    </div>
    <div class="fk-lang-switcher">
      <?php foreach (['fr'=>'FR','en'=>'EN','ar'=>'عربي'] as $lc=>$ll): ?>
      <a href="?lang=<?= $lc ?>" class="<?= $lang===$lc?'active':'' ?>"><?= $ll ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</header>

<!-- Hero -->
<div class="fk-public-hero" style="background:linear-gradient(135deg, <?= htmlspecialchars($primary_color) ?> 0%, <?= htmlspecialchars($hotel['secondary_color']??'#52B788') ?> 100%)">
  <h1><?= sanitize($t['title']) ?></h1>
  <p><?= sanitize($t['subtitle']) ?></p>
  <div class="fk-trust-blocks">
    <?php foreach (['✓'=>$t['trust1'],'🏨'=>$t['trust2'],'💬'=>$t['trust3'],'🌐'=>$t['trust4']] as $ico=>$txt): ?>
    <div class="fk-trust-item">
      <div class="fk-trust-icon"><?= $ico ?></div>
      <div><?= sanitize($txt) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Form card -->
<div class="fk-form-wrap">
  <?php if (!empty($errors)): ?>
  <div class="fk-alert-box fk-alert-error">
    <ul style="margin:0;padding-left:18px">
      <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form method="POST" id="bookingForm">
    <input type="hidden" name="client_language" value="<?= $lang ?>">

    <!-- Parent info -->
    <div class="fk-form-section">
      <div class="fk-form-section-title"><?= $t['form_title'] ?></div>
      <div class="fk-form-grid-2">
        <div class="fk-field"><label><?= $t['name'] ?> *</label>
          <input type="text" name="client_name" class="fk-public-input" value="<?= sanitize($_POST['client_name']??'') ?>" required></div>
        <div class="fk-field"><label><?= $t['whatsapp'] ?> *</label>
          <input type="tel" name="client_whatsapp" class="fk-public-input" value="<?= sanitize($_POST['client_whatsapp']??'') ?>" placeholder="+212 6XX XXX XXX" required></div>
        <div class="fk-field"><label><?= $t['email'] ?></label>
          <input type="email" name="client_email" class="fk-public-input" value="<?= sanitize($_POST['client_email']??'') ?>"></div>
        <div class="fk-field"><label><?= $t['room'] ?></label>
          <input type="text" name="room_number" class="fk-public-input" value="<?= sanitize($_POST['room_number']??'') ?>" placeholder="Ex: 312"></div>
      </div>
    </div>

    <!-- Service -->
    <div class="fk-form-section">
      <div class="fk-form-section-title">🗓 Service</div>
      <div class="fk-form-grid-2">
        <div class="fk-field"><label><?= $t['date'] ?> *</label>
          <input type="date" name="service_date" class="fk-public-input" value="<?= sanitize($_POST['service_date']??'') ?>" min="<?= $min_date ?>" required></div>
        <div class="fk-field"><label><?= $t['time'] ?> *</label>
          <input type="time" name="start_time" class="fk-public-input" value="<?= sanitize($_POST['start_time']??'10:00') ?>" required></div>
        <div class="fk-field"><label><?= $t['duration'] ?></label>
          <select name="duration_minutes" class="fk-public-input">
            <?php foreach ([60=>'1h',90=>'1h30',120=>'2h',150=>'2h30',180=>'3h',240=>'4h',300=>'5h',360=>'6h',480=>'8h',720=>'12h'] as $m=>$l): ?>
            <option value="<?= $m ?>" <?= ($_POST['duration_minutes']??120)==$m?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="fk-field"><label><?= $t['children'] ?> *</label>
          <select name="children_count" id="childCount" class="fk-public-input" onchange="generateChildForms(this.value)">
            <?php for ($n=1;$n<=8;$n++): ?>
            <option value="<?= $n ?>" <?= ($_POST['children_count']??1)==$n?'selected':'' ?>><?= $n ?></option>
            <?php endfor; ?>
          </select></div>
      </div>
    </div>

    <!-- Children details -->
    <div class="fk-form-section">
      <div class="fk-form-section-title">👶 <?= $lang==='ar'?'تفاصيل الأطفال':($lang==='en'?'Children details':'Détails des enfants') ?></div>
      <div id="childrenContainer"></div>
    </div>

    <!-- Notes -->
    <div class="fk-form-section">
      <div class="fk-field"><label><?= $t['notes'] ?></label>
        <textarea name="special_needs" class="fk-public-input" rows="3" placeholder="<?= $lang==='ar'?'معلومات إضافية...':($lang==='en'?'Any additional information...':'Informations complémentaires, besoins spéciaux...') ?>"><?= sanitize($_POST['special_needs']??'') ?></textarea>
      </div>
    </div>

    <!-- Price notice -->
    <div class="fk-notice-box">
      <div class="fk-notice-icon">ℹ️</div>
      <p><?= sanitize($t['notice']) ?></p>
    </div>

    <!-- Rules -->
    <div class="fk-rules-box">
      <div class="fk-rules-title">📋 <?= sanitize($t['rules_title']) ?></div>
      <ul>
        <li><?= sanitize($t['rule1']) ?></li>
        <li><?= sanitize($t['rule2']) ?></li>
        <li><?= sanitize($t['rule3']) ?></li>
      </ul>
    </div>

    <button type="submit" class="fk-submit-btn" style="background:<?= htmlspecialchars($primary_color) ?>">
      <?= sanitize($t['submit']) ?>
    </button>
  </form>
</div>

<footer class="fk-public-footer">
  <p>© <?= date('Y') ?> Faiza Multiservice · Agadir, Maroc</p>
</footer>

<script src="<?= $base_url ?>/assets/js/public.js"></script>
<script>
const LANG = '<?= $lang ?>';
const T_CHILD_NAME = '<?= $t["child_name"] ?>';
const T_CHILD_AGE  = '<?= $t["child_age"] ?>';
const T_CHILD_ALG  = '<?= $t["child_allergies"] ?>';
const T_CHILD_NDS  = '<?= $t["child_needs"] ?>';

function generateChildForms(count) {
  const c = document.getElementById('childrenContainer');
  c.innerHTML = '';
  for (let i = 0; i < parseInt(count); i++) {
    c.innerHTML += `
    <div class="fk-child-card">
      <div class="fk-child-number">${LANG==='ar'?'الطفل':'Enfant'} ${i+1}</div>
      <div class="fk-form-grid-4">
        <div class="fk-field"><label>${T_CHILD_NAME}</label>
          <input type="text" name="child_name[]" class="fk-public-input"></div>
        <div class="fk-field"><label>${T_CHILD_AGE}</label>
          <input type="number" name="child_age[]" class="fk-public-input" min="0" max="18" placeholder="${LANG==='ar'?'سنوات':'ans'}"></div>
        <div class="fk-field"><label>${T_CHILD_ALG}</label>
          <input type="text" name="child_allergies[]" class="fk-public-input" placeholder="${LANG==='ar'?'لا يوجد':'Aucune'}"></div>
        <div class="fk-field"><label>${T_CHILD_NDS}</label>
          <input type="text" name="child_needs[]" class="fk-public-input"></div>
      </div>
    </div>`;
  }
}
document.addEventListener('DOMContentLoaded', () => generateChildForms(<?= (int)($_POST['children_count']??1) ?>));
</script>
</body>
</html>
