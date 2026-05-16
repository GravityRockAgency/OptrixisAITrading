<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$lang = $_GET['lang'] ?? $_POST['client_language'] ?? $_COOKIE['fk_lang'] ?? 'fr';
if (!in_array($lang, ['fr','en','ar'])) $lang = 'fr';
$source = $_GET['source'] ?? 'city_link';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name     = trim($_POST['client_name']    ?? '');
    $client_whatsapp = trim($_POST['client_whatsapp'] ?? '');
    $client_email    = trim($_POST['client_email']   ?? '');
    $client_language = $_POST['client_language']     ?? $lang;
    $city            = trim($_POST['city']           ?? 'Agadir');
    $area            = trim($_POST['area']           ?? '');
    $address         = trim($_POST['address']        ?? '');
    $location_notes  = trim($_POST['location_notes'] ?? '');
    $service_date    = $_POST['service_date']        ?? '';
    $start_time      = $_POST['start_time']          ?? '';
    $duration_mins   = (int)($_POST['duration_minutes'] ?? 120);
    $children_count  = max(1,(int)($_POST['children_count']  ?? 1));
    $special_needs   = trim($_POST['special_needs']  ?? '');
    $lang            = $client_language;

    if (!$client_name)     $errors[] = 'Le nom est obligatoire.';
    if (!$client_whatsapp) $errors[] = 'Le WhatsApp est obligatoire.';
    if (!$service_date)    $errors[] = 'La date est obligatoire.';
    if (!$start_time)      $errors[] = 'L\'heure est obligatoire.';

    $min_hours = (int)get_setting('min_booking_hours', '24');
    if ($service_date && $start_time) {
        if (strtotime($service_date.' '.$start_time) < (time() + $min_hours * 3600)) {
            $errors[] = "La demande doit être soumise au moins $min_hours heures avant le service.";
        }
    }

    if (empty($errors)) {
        $end_ts   = strtotime($service_date . ' ' . $start_time) + ($duration_mins * 60);
        $end_time = date('H:i:s', $end_ts);
        $reference = generate_reference();
        $token = generate_secure_token(20);

        // Get city hotel
        $city_hotel = db_fetch("SELECT id FROM hotels WHERE slug='city' LIMIT 1", []);

        $booking_id = db_insert('bookings', [
            'reference'        => $reference,
            'type'             => 'city',
            'source'           => $source,
            'hotel_id'         => $city_hotel ? $city_hotel['id'] : null,
            'client_name'      => $client_name,
            'client_whatsapp'  => $client_whatsapp,
            'client_email'     => $client_email,
            'client_language'  => $client_language,
            'city'             => $city,
            'area'             => $area,
            'address'          => $address,
            'location_notes'   => $location_notes,
            'service_date'     => $service_date,
            'start_time'       => $start_time,
            'duration_minutes' => $duration_mins,
            'end_time'         => $end_time,
            'children_count'   => $children_count,
            'special_needs'    => $special_needs,
            'status'           => 'new',
            'payment_status'   => 'pending',
            'price_status'     => 'pending',
            'secure_token'     => $token,
        ]);

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

        try {
            db_insert('notifications', [
                'type'       => 'new_booking',
                'title'      => 'Nouvelle demande City',
                'message'    => "$client_name — $city — $service_date",
                'booking_id' => $booking_id,
            ]);
        } catch(Exception $e){}

        $base_url = defined('BASE_URL') ? BASE_URL : '';
        header("Location: $base_url/success/$reference?lang=$lang");
        exit;
    }
}

$t = [
    'fr' => [
        'title'   => 'Service babysitting Agadir & région',
        'intro'   => "Que vous soyez en séjour à Agadir ou résident dans la région, notre service est conçu pour répondre à vos besoins.\n\nEnvoyez votre demande en toute simplicité : notre équipe vous contactera rapidement via WhatsApp afin de confirmer la disponibilité et vous fournir tous les détails du service.",
        'notice'  => 'Le tarif sera communiqué après vérification de la disponibilité et des détails du service.',
        'rule1'   => 'Demande au moins 24 heures à l\'avance.',
        'rule2'   => 'Annulation au moins 3 heures avant le service.',
        'rule3'   => 'Annulation tardive : remboursement limité à 50%.',
        'submit'  => 'Envoyer la demande',
    ],
    'en' => [
        'title'   => 'Babysitting service – Agadir & region',
        'intro'   => "Whether you are visiting Agadir or living in the area, our service is designed to meet your needs.\n\nSend your request easily: our team will contact you quickly via WhatsApp to confirm availability and provide all service details.",
        'notice'  => 'The price will be communicated after verifying availability and service details.',
        'rule1'   => 'Request at least 24 hours in advance.',
        'rule2'   => 'Cancellation at least 3 hours before the service.',
        'rule3'   => 'Late cancellation: refund limited to 50%.',
        'submit'  => 'Send request',
    ],
    'ar' => [
        'title'   => 'خدمة جليسة الأطفال – أكادير والمنطقة',
        'intro'   => "سواء كنتم في إقامة بأكادير أو مقيمين في المنطقة، خدمتنا مصممة لتلبية احتياجاتكم.\n\nأرسلوا طلبكم بكل سهولة: سيتواصل معكم فريقنا بسرعة عبر واتساب لتأكيد التوفر وتزويدكم بكافة تفاصيل الخدمة.",
        'notice'  => 'سيتم إبلاغكم بالسعر بعد التحقق من التوفر وتفاصيل الخدمة.',
        'rule1'   => 'الطلب قبل 24 ساعة على الأقل.',
        'rule2'   => 'الإلغاء قبل 3 ساعات على الأقل من الخدمة.',
        'rule3'   => 'الإلغاء المتأخر: استرداد محدود بـ 50%.',
        'submit'  => 'إرسال الطلب',
    ],
][$lang] ?? [];

$dir      = $lang === 'ar' ? 'rtl' : 'ltr';
$base_url = defined('BASE_URL') ? BASE_URL : '';
$min_date = date('Y-m-d', time() + 86400);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>City / Clients externes – Faiza Kids Concierge</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/public.css">
<style>:root{--hotel-color:#2D6A4F;--hotel-accent:#E8C342;}</style>
</head>
<body class="fk-public-page">

<header class="fk-public-header">
  <div class="fk-public-header-inner">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:36px;height:36px;border-radius:50%;background:#0D2B1D;color:#52B788;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center">F</div>
      <div>
        <div style="font-size:13px;font-weight:700;color:#1A2E24">Faiza Kids Concierge</div>
        <div style="font-size:11px;color:#6B7A72">Agadir & région</div>
      </div>
    </div>
    <div class="fk-lang-switcher">
      <?php foreach (['fr'=>'FR','en'=>'EN','ar'=>'عربي'] as $lc=>$ll): ?>
      <a href="?lang=<?= $lc ?>" class="<?= $lang===$lc?'active':'' ?>"><?= $ll ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</header>

<div class="fk-public-hero" style="background:linear-gradient(135deg,#0D2B1D 0%,#2D6A4F 100%)">
  <h1><?= sanitize($t['title']) ?></h1>
  <p style="white-space:pre-line;max-width:600px;margin:0 auto"><?= sanitize($t['intro']) ?></p>
</div>

<div class="fk-form-wrap">
  <?php if (!empty($errors)): ?>
  <div class="fk-alert-box fk-alert-error">
    <ul style="margin:0;padding-left:18px"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <form method="POST" id="cityBookingForm">
    <input type="hidden" name="client_language" value="<?= $lang ?>">

    <!-- Client -->
    <div class="fk-form-section">
      <div class="fk-form-section-title"><?= $lang==='ar'?'معلومات العميل':($lang==='en'?'Your information':'Vos informations') ?></div>
      <div class="fk-form-grid-2">
        <div class="fk-field"><label><?= $lang==='ar'?'الاسم الكامل *':($lang==='en'?'Full name *':'Nom complet *') ?></label>
          <input type="text" name="client_name" class="fk-public-input" value="<?= sanitize($_POST['client_name']??'') ?>" required></div>
        <div class="fk-field"><label><?= $lang==='ar'?'رقم واتساب *':($lang==='en'?'WhatsApp *':'WhatsApp *') ?></label>
          <input type="tel" name="client_whatsapp" class="fk-public-input" value="<?= sanitize($_POST['client_whatsapp']??'') ?>" placeholder="+212 6XX XXX XXX" required></div>
        <div style="grid-column:span 2" class="fk-field"><label><?= $lang==='ar'?'البريد الإلكتروني':($lang==='en'?'Email (optional)':'Email (optionnel)') ?></label>
          <input type="email" name="client_email" class="fk-public-input" value="<?= sanitize($_POST['client_email']??'') ?>"></div>
      </div>
    </div>

    <!-- Location -->
    <div class="fk-form-section">
      <div class="fk-form-section-title">📍 <?= $lang==='ar'?'الموقع':($lang==='en'?'Location':'Localisation') ?></div>
      <div class="fk-form-grid-2">
        <div class="fk-field"><label><?= $lang==='ar'?'المدينة':($lang==='en'?'City':'Ville') ?></label>
          <input type="text" name="city" class="fk-public-input" value="<?= sanitize($_POST['city']??'Agadir') ?>"></div>
        <div class="fk-field"><label><?= $lang==='ar'?'الحي':($lang==='en'?'Area / District':'Quartier / Secteur') ?></label>
          <input type="text" name="area" class="fk-public-input" value="<?= sanitize($_POST['area']??'') ?>"></div>
        <div style="grid-column:span 2" class="fk-field"><label><?= $lang==='ar'?'العنوان الكامل':($lang==='en'?'Full address':'Adresse complète') ?></label>
          <input type="text" name="address" class="fk-public-input" value="<?= sanitize($_POST['address']??'') ?>" placeholder="<?= $lang==='ar'?'الشارع، البناية، الطابق...':'Rue, immeuble, numéro...' ?>"></div>
        <div style="grid-column:span 2" class="fk-field"><label><?= $lang==='ar'?'ملاحظات للعثور على العنوان':($lang==='en'?'Location notes':'Indications pour trouver l\'adresse') ?></label>
          <input type="text" name="location_notes" class="fk-public-input" value="<?= sanitize($_POST['location_notes']??'') ?>"></div>
      </div>
    </div>

    <!-- Service -->
    <div class="fk-form-section">
      <div class="fk-form-section-title">🗓 <?= $lang==='ar'?'تفاصيل الخدمة':($lang==='en'?'Service details':'Détails du service') ?></div>
      <div class="fk-form-grid-2">
        <div class="fk-field"><label><?= $lang==='ar'?'التاريخ *':($lang==='en'?'Date *':'Date *') ?></label>
          <input type="date" name="service_date" class="fk-public-input" value="<?= sanitize($_POST['service_date']??'') ?>" min="<?= $min_date ?>" required></div>
        <div class="fk-field"><label><?= $lang==='ar'?'وقت البداية *':($lang==='en'?'Start time *':'Heure de début *') ?></label>
          <input type="time" name="start_time" class="fk-public-input" value="<?= sanitize($_POST['start_time']??'10:00') ?>" required></div>
        <div class="fk-field"><label><?= $lang==='ar'?'المدة':($lang==='en'?'Duration':'Durée') ?></label>
          <select name="duration_minutes" class="fk-public-input">
            <?php foreach ([60=>'1h',90=>'1h30',120=>'2h',150=>'2h30',180=>'3h',240=>'4h',300=>'5h',360=>'6h',480=>'8h',720=>'12h'] as $m=>$l): ?>
            <option value="<?= $m ?>" <?= ($_POST['duration_minutes']??120)==$m?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="fk-field"><label><?= $lang==='ar'?'عدد الأطفال *':($lang==='en'?'Number of children *':'Nombre d\'enfants *') ?></label>
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
      <div class="fk-field"><label><?= $lang==='ar'?'ملاحظات إضافية':($lang==='en'?'Additional notes':'Notes complémentaires') ?></label>
        <textarea name="special_needs" class="fk-public-input" rows="3"><?= sanitize($_POST['special_needs']??'') ?></textarea></div>
    </div>

    <!-- Price notice (City = NO price shown) -->
    <div class="fk-notice-box">
      <div class="fk-notice-icon">💬</div>
      <p><?= sanitize($t['notice']) ?></p>
    </div>

    <!-- Rules -->
    <div class="fk-rules-box">
      <div class="fk-rules-title">📋 <?= $lang==='ar'?'القواعد':($lang==='en'?'Rules':'Règles') ?></div>
      <ul>
        <li><?= sanitize($t['rule1']) ?></li>
        <li><?= sanitize($t['rule2']) ?></li>
        <li><?= sanitize($t['rule3']) ?></li>
      </ul>
    </div>

    <button type="submit" class="fk-submit-btn"><?= sanitize($t['submit']) ?></button>
  </form>
</div>

<footer class="fk-public-footer"><p>© <?= date('Y') ?> Faiza Multiservice · Agadir, Maroc</p></footer>

<script src="<?= $base_url ?>/assets/js/public.js"></script>
<script>
const LANG = '<?= $lang ?>';
function generateChildForms(count) {
  const c = document.getElementById('childrenContainer');
  c.innerHTML = '';
  for (let i = 0; i < parseInt(count); i++) {
    c.innerHTML += `<div class="fk-child-card">
      <div class="fk-child-number">${LANG==='ar'?'الطفل':(LANG==='en'?'Child':'Enfant')} ${i+1}</div>
      <div class="fk-form-grid-4">
        <div class="fk-field"><label>${LANG==='ar'?'الاسم':(LANG==='en'?'Name':'Prénom')}</label>
          <input type="text" name="child_name[]" class="fk-public-input"></div>
        <div class="fk-field"><label>${LANG==='ar'?'العمر':(LANG==='en'?'Age':'Âge')}</label>
          <input type="number" name="child_age[]" class="fk-public-input" min="0" max="18"></div>
        <div class="fk-field"><label>${LANG==='ar'?'الحساسية':(LANG==='en'?'Allergies':'Allergies')}</label>
          <input type="text" name="child_allergies[]" class="fk-public-input"></div>
        <div class="fk-field"><label>${LANG==='ar'?'احتياجات':(LANG==='en'?'Special needs':'Besoins')}</label>
          <input type="text" name="child_needs[]" class="fk-public-input"></div>
      </div></div>`;
  }
}
document.addEventListener('DOMContentLoaded', () => generateChildForms(<?= (int)($_POST['children_count']??1) ?>));
</script>
</body>
</html>
