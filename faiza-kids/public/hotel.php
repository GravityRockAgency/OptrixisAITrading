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
$color_end     = $hotel['secondary_color'] ?? '#52B788';

$i18n = [
    'fr' => [
        'title'          => $hotel['public_title'] ?? 'Réservez une baby-sitter de confiance',
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
        'notice_title'   => 'Note importante',
        'rules_title'    => 'Règles du service',
        'rule1'          => 'Demande au moins 24 heures avant l\'heure souhaitée.',
        'rule2'          => 'Annulation au moins 3 heures avant le service.',
        'rule3'          => 'Annulation tardive : remboursement limité à 50%.',
        'submit'         => 'Envoyer la demande',
        'trust1'         => 'Baby-sitters sélectionnées',
        'trust2'         => 'Coordination avec l\'hôtel',
        'trust3'         => 'Support WhatsApp',
        'trust4'         => 'Service multilingue',
        'sec_a'          => 'Vos informations',
        'sec_b'          => 'Date & heure du service',
        'sec_c'          => 'Détails des enfants',
        'sec_d'          => 'Notes complémentaires',
        'child_label'    => 'Enfant',
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
        'notice_title'   => 'Important note',
        'rules_title'    => 'Service rules',
        'rule1'          => 'Request at least 24 hours before the desired time.',
        'rule2'          => 'Cancellation at least 3 hours before the service.',
        'rule3'          => 'Late cancellation: refund limited to 50%.',
        'submit'         => 'Send request',
        'trust1'         => 'Vetted babysitters',
        'trust2'         => 'Hotel coordination',
        'trust3'         => 'WhatsApp support',
        'trust4'         => 'Multilingual service',
        'sec_a'          => 'Your information',
        'sec_b'          => 'Service date & time',
        'sec_c'          => 'Children details',
        'sec_d'          => 'Additional notes',
        'child_label'    => 'Child',
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
        'notice_title'   => 'ملاحظة مهمة',
        'rules_title'    => 'قواعد الخدمة',
        'rule1'          => 'الطلب قبل 24 ساعة على الأقل من الوقت المطلوب.',
        'rule2'          => 'الإلغاء قبل 3 ساعات على الأقل من الخدمة.',
        'rule3'          => 'الإلغاء المتأخر: استرداد محدود بـ 50%.',
        'submit'         => 'إرسال الطلب',
        'trust1'         => 'جليسات مختارة',
        'trust2'         => 'تنسيق مع الفندق',
        'trust3'         => 'دعم واتساب',
        'trust4'         => 'خدمة متعددة اللغات',
        'sec_a'          => 'معلوماتكم',
        'sec_b'          => 'تاريخ ووقت الخدمة',
        'sec_c'          => 'تفاصيل الأطفال',
        'sec_d'          => 'ملاحظات إضافية',
        'child_label'    => 'الطفل',
    ],
];

$t   = $i18n[$lang] ?? $i18n['fr'];
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$base_url = defined('BASE_URL') ? BASE_URL : '';
$min_date = date('Y-m-d', time() + 86400);
$co_logo  = get_setting('company_logo', '');
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
<style>:root{--hotel-color:<?= htmlspecialchars($primary_color) ?>;--hotel-color-end:<?= htmlspecialchars($color_end) ?>;}</style>
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
        <div class="pk-brand-sub"><?= $lang==='ar'?'خدمة احترافية':($lang==='en'?'Professional service':'Service professionnel') ?></div>
      </div>
    </div>

    <div class="pk-header-hotel">
      <?php if (!empty($hotel['logo_url'])): ?>
        <img src="<?= htmlspecialchars($hotel['logo_url']) ?>" alt="<?= sanitize($hotel['name']) ?>" class="pk-header-hotel-logo">
      <?php else: ?>
        <div class="pk-header-hotel-badge"><?= mb_strtoupper(mb_substr($hotel['name'], 0, 2)) ?></div>
      <?php endif; ?>
      <span class="pk-header-hotel-name"><?= sanitize($hotel['name']) ?></span>
    </div>

    <nav class="pk-langs">
      <?php foreach (['fr'=>'FR','en'=>'EN','ar'=>'عربي'] as $lc=>$ll): ?>
      <a href="?lang=<?= $lc ?>" class="pk-lang<?= $lang===$lc?' is-active':'' ?>"><?= $ll ?></a>
      <?php endforeach; ?>
    </nav>

  </div>
</header>

<section class="pk-hero">
  <div class="pk-hero-inner">
    <div class="pk-hero-chip">⭐ <?= $lang==='ar'?'خدمة موثوقة':($lang==='en'?'Trusted service':'Service de confiance') ?></div>
    <h1 class="pk-hero-h1"><?= sanitize($t['title']) ?></h1>
    <p class="pk-hero-p"><?= sanitize($t['subtitle']) ?></p>
  </div>
</section>

<div class="pk-trusts">
  <div class="pk-trust"><div class="pk-trust-icon">✓</div><div class="pk-trust-label"><?= sanitize($t['trust1']) ?></div></div>
  <div class="pk-trust"><div class="pk-trust-icon">🏨</div><div class="pk-trust-label"><?= sanitize($t['trust2']) ?></div></div>
  <div class="pk-trust"><div class="pk-trust-icon">💬</div><div class="pk-trust-label"><?= sanitize($t['trust3']) ?></div></div>
  <div class="pk-trust"><div class="pk-trust-icon">🌐</div><div class="pk-trust-label"><?= sanitize($t['trust4']) ?></div></div>
</div>

<main class="pk-main">
  <div class="pk-card">

    <?php if (!empty($errors)): ?>
    <div class="pk-errors">
      <div class="pk-errors-title"><?= $lang==='ar'?'يرجى تصحيح الأخطاء التالية':($lang==='en'?'Please fix the following errors':'Veuillez corriger les erreurs suivantes') ?></div>
      <ul>
        <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" id="bookingForm">
      <input type="hidden" name="client_language" value="<?= $lang ?>">

      <!-- A: Client information -->
      <div class="pk-section">
        <div class="pk-section-head">
          <span class="pk-section-num">A</span>
          <span class="pk-section-title"><?= sanitize($t['sec_a']) ?></span>
        </div>
        <div class="pk-grid-2">
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['name']) ?><span class="pk-req">*</span></label>
            <input type="text" name="client_name" class="pk-input" value="<?= sanitize($_POST['client_name']??'') ?>" required>
          </div>
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['whatsapp']) ?><span class="pk-req">*</span></label>
            <input type="tel" name="client_whatsapp" class="pk-input" value="<?= sanitize($_POST['client_whatsapp']??'') ?>" placeholder="+212 6XX XXX XXX" required>
          </div>
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['email']) ?></label>
            <input type="email" name="client_email" class="pk-input" value="<?= sanitize($_POST['client_email']??'') ?>">
          </div>
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['room']) ?></label>
            <input type="text" name="room_number" class="pk-input" value="<?= sanitize($_POST['room_number']??'') ?>" placeholder="<?= $lang==='ar'?'مثال: 312':'Ex: 312' ?>">
          </div>
        </div>
      </div>

      <!-- B: Service date & time -->
      <div class="pk-section">
        <div class="pk-section-head">
          <span class="pk-section-num">B</span>
          <span class="pk-section-title">🗓 <?= sanitize($t['sec_b']) ?></span>
        </div>
        <div class="pk-grid-2">
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['date']) ?><span class="pk-req">*</span></label>
            <input type="date" name="service_date" class="pk-input" value="<?= sanitize($_POST['service_date']??'') ?>" min="<?= $min_date ?>" required>
          </div>
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['time']) ?><span class="pk-req">*</span></label>
            <input type="time" name="start_time" class="pk-input" value="<?= sanitize($_POST['start_time']??'10:00') ?>" required>
          </div>
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['duration']) ?></label>
            <select name="duration_minutes" class="pk-select">
              <?php foreach ([60=>'1h',90=>'1h30',120=>'2h',150=>'2h30',180=>'3h',240=>'4h',300=>'5h',360=>'6h',480=>'8h',720=>'12h'] as $m=>$l): ?>
              <option value="<?= $m ?>" <?= ($_POST['duration_minutes']??120)==$m?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="pk-field">
            <label class="pk-label"><?= sanitize($t['children']) ?><span class="pk-req">*</span></label>
            <select name="children_count" id="childCount" class="pk-select">
              <?php for ($n=1;$n<=8;$n++): ?>
              <option value="<?= $n ?>" <?= ($_POST['children_count']??1)==$n?'selected':'' ?>><?= $n ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- C: Children details -->
      <div class="pk-section">
        <div class="pk-section-head">
          <span class="pk-section-num">C</span>
          <span class="pk-section-title">👶 <?= sanitize($t['sec_c']) ?></span>
        </div>
        <div id="childrenContainer"></div>
      </div>

      <!-- D: Additional notes -->
      <div class="pk-section">
        <div class="pk-section-head">
          <span class="pk-section-num">D</span>
          <span class="pk-section-title"><?= sanitize($t['sec_d']) ?></span>
        </div>
        <div class="pk-field">
          <label class="pk-label"><?= sanitize($t['notes']) ?></label>
          <textarea name="special_needs" class="pk-textarea" rows="3" placeholder="<?= $lang==='ar'?'معلومات إضافية...':($lang==='en'?'Any additional information...':'Informations complémentaires, besoins spéciaux...') ?>"><?= sanitize($_POST['special_needs']??'') ?></textarea>
        </div>
      </div>

      <!-- Price notice -->
      <div class="pk-notice">
        <div class="pk-notice-ico">ℹ️</div>
        <div>
          <div class="pk-notice-title"><?= sanitize($t['notice_title']) ?></div>
          <div class="pk-notice-text"><?= sanitize($t['notice']) ?></div>
        </div>
      </div>

      <!-- Service rules -->
      <div class="pk-rules">
        <div class="pk-rules-title">📋 <?= sanitize($t['rules_title']) ?></div>
        <ul>
          <li><?= sanitize($t['rule1']) ?></li>
          <li><?= sanitize($t['rule2']) ?></li>
          <li><?= sanitize($t['rule3']) ?></li>
        </ul>
      </div>

      <div class="pk-submit-section">
        <button type="submit" class="pk-submit-btn"><?= sanitize($t['submit']) ?></button>
      </div>
    </form>

  </div>
</main>

<footer class="pk-footer">
  <p>© <?= date('Y') ?> Faiza Multiservice · Agadir, Maroc</p>
</footer>

<script src="<?= $base_url ?>/assets/js/public.js"></script>
<script>
(function() {
  var INITIAL_COUNT = <?= (int)($_POST['children_count'] ?? 1) ?>;
  var LANG = '<?= $lang ?>';
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.generateChildForms === 'function') {
      window.generateChildForms(INITIAL_COUNT, LANG);
    }
    var sel = document.getElementById('childCount');
    if (sel) {
      sel.addEventListener('change', function() {
        if (typeof window.generateChildForms === 'function') {
          window.generateChildForms(parseInt(this.value), LANG);
        }
      });
    }
  });
})();
</script>
</body>
</html>
