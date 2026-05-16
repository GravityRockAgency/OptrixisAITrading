<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_login();

$page_title    = 'Réservation manuelle';
$page_subtitle = 'Créer une demande reçue par téléphone, WhatsApp, réception hôtel ou autre canal.';

$hotels      = db_fetch_all("SELECT id, name, code FROM hotels WHERE is_active=1 ORDER BY sort_order", []);
$babysitters = db_fetch_all("SELECT id, full_name FROM babysitters WHERE is_active=1 AND status='available' ORDER BY full_name", []);
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type            = $_POST['type'] ?? '';
    $client_name     = trim($_POST['client_name'] ?? '');
    $client_whatsapp = trim($_POST['client_whatsapp'] ?? '');
    $client_email    = trim($_POST['client_email'] ?? '');
    $client_language = $_POST['client_language'] ?? 'fr';
    $hotel_id        = !empty($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : null;
    $room_number     = trim($_POST['room_number'] ?? '');
    $city            = trim($_POST['city'] ?? '');
    $area            = trim($_POST['area'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $location_notes  = trim($_POST['location_notes'] ?? '');
    $service_date    = $_POST['service_date'] ?? '';
    $start_time      = $_POST['start_time'] ?? '';
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 120);
    $children_count  = max(1, (int)($_POST['children_count'] ?? 1));
    $special_needs   = trim($_POST['special_needs'] ?? '');
    $internal_notes  = trim($_POST['internal_notes'] ?? '');
    $babysitter_id   = !empty($_POST['babysitter_id']) ? (int)$_POST['babysitter_id'] : null;
    $babysitters_count = max(1, (int)($_POST['babysitters_count'] ?? 1));
    $suggested_price = !empty($_POST['suggested_price']) ? (float)$_POST['suggested_price'] : null;
    $final_price     = !empty($_POST['final_price']) ? (float)$_POST['final_price'] : null;
    $price_status    = $_POST['price_status'] ?? 'pending';
    $source          = $_POST['source'] ?? 'manual';
    $initial_status  = $_POST['initial_status'] ?? 'pending';
    $payment_required = $_POST['payment_required'] ?? 'no';

    if (!$type)            $errors[] = 'Le type de réservation est obligatoire.';
    if (!$client_name)     $errors[] = 'Le nom du client est obligatoire.';
    if (!$client_whatsapp) $errors[] = 'Le numéro WhatsApp est obligatoire.';
    if (!$service_date)    $errors[] = 'La date du service est obligatoire.';
    if (!$start_time)      $errors[] = 'L\'heure de début est obligatoire.';

    if (empty($errors)) {
        $end_ts   = strtotime($service_date . ' ' . $start_time) + ($duration_minutes * 60);
        $end_time = date('H:i:s', $end_ts);
        $reference = generate_reference();

        $booking_id = db_insert('bookings', [
            'reference'        => $reference,
            'type'             => $type,
            'source'           => $source,
            'hotel_id'         => $hotel_id,
            'client_name'      => $client_name,
            'client_whatsapp'  => $client_whatsapp,
            'client_email'     => $client_email,
            'client_language'  => $client_language,
            'room_number'      => $room_number,
            'city'             => $city,
            'area'             => $area,
            'address'          => $address,
            'location_notes'   => $location_notes,
            'service_date'     => $service_date,
            'start_time'       => $start_time,
            'duration_minutes' => $duration_minutes,
            'end_time'         => $end_time,
            'children_count'   => $children_count,
            'special_needs'    => $special_needs,
            'internal_notes'   => $internal_notes,
            'babysitter_id'    => $babysitter_id,
            'babysitters_count'=> $babysitters_count,
            'suggested_price'  => $suggested_price,
            'final_price'      => $final_price,
            'price_status'     => $price_status,
            'status'           => $initial_status,
            'payment_status'   => $payment_required === 'yes' ? 'pending' : 'not_required',
        ]);

        $child_names = $_POST['child_name']       ?? [];
        $child_ages  = $_POST['child_age']        ?? [];
        $child_allrg = $_POST['child_allergies']  ?? [];
        $child_needs = $_POST['child_needs']      ?? [];
        $child_notes = $_POST['child_notes']      ?? [];

        for ($i = 0; $i < $children_count; $i++) {
            db_insert('booking_children', [
                'booking_id'   => $booking_id,
                'child_name'   => $child_names[$i] ?? '',
                'age'          => !empty($child_ages[$i]) ? (int)$child_ages[$i] : null,
                'allergies'    => $child_allrg[$i] ?? '',
                'special_needs'=> $child_needs[$i] ?? '',
                'notes'        => $child_notes[$i] ?? '',
                'sort_order'   => $i,
            ]);
        }

        log_activity('Réservation manuelle créée', "Référence: $reference | Client: $client_name", $booking_id);
        header("Location: /admin/bookings/view/$booking_id");
        exit;
    }
}

include 'layout-top.php';
?>
<div class="fk-page-header">
  <div>
    <h1 class="fk-page-title"><?= sanitize($page_title) ?></h1>
    <p class="fk-page-subtitle"><?= sanitize($page_subtitle) ?></p>
  </div>
  <a href="/admin/bookings" class="fk-btn fk-btn-secondary">← Réservations</a>
</div>

<?php if ($errors): ?>
<div class="fk-alert fk-alert-error" style="margin-bottom:20px">
  <strong>Veuillez corriger les erreurs suivantes :</strong>
  <ul style="margin:8px 0 0 18px">
    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<form method="POST" id="manualBookingForm">
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

<!-- LEFT COLUMN -->
<div style="display:flex;flex-direction:column;gap:16px">

<!-- Section 1: Type -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">Type de réservation</h3></div>
  <div style="display:flex;gap:10px;margin-bottom:16px">
    <label class="fk-type-pill" id="pill-hotel">
      <input type="radio" name="type" value="hotel" <?= ($_POST['type']??'hotel')==='hotel'?'checked':'' ?> onchange="toggleType('hotel')">
      <span>🏨 Réservation hôtel</span>
    </label>
    <label class="fk-type-pill" id="pill-city">
      <input type="radio" name="type" value="city" <?= ($_POST['type']??'')==='city'?'checked':'' ?> onchange="toggleType('city')">
      <span>🏙️ Réservation City</span>
    </label>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="fk-form-group">
      <label class="fk-label">Source de la demande</label>
      <select name="source" class="fk-select">
        <option value="manual">Manuel (téléphone/walk-in)</option>
        <option value="whatsapp">WhatsApp</option>
        <option value="hotel_reception">Réception hôtel</option>
        <option value="qr_hotel">QR Code hôtel</option>
        <option value="nfc_hotel">NFC hôtel</option>
        <option value="city_link">Lien City</option>
        <option value="instagram">Instagram</option>
      </select>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Statut initial</label>
      <select name="initial_status" class="fk-select">
        <option value="new">Nouvelle demande</option>
        <option value="pending" selected>En attente</option>
        <option value="confirmed">Confirmée directement</option>
      </select>
    </div>
  </div>
</div>

<!-- Section 2: Client -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">Informations client</h3></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="fk-form-group">
      <label class="fk-label">Nom complet <span style="color:#EF4444">*</span></label>
      <input type="text" name="client_name" class="fk-input" value="<?= sanitize($_POST['client_name']??'') ?>" placeholder="Prénom Nom" required>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">WhatsApp <span style="color:#EF4444">*</span></label>
      <input type="text" name="client_whatsapp" class="fk-input" value="<?= sanitize($_POST['client_whatsapp']??'') ?>" placeholder="+212 6XX XXX XXX" required>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Email</label>
      <input type="email" name="client_email" class="fk-input" value="<?= sanitize($_POST['client_email']??'') ?>" placeholder="client@email.com">
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Langue de communication</label>
      <select name="client_language" class="fk-select">
        <option value="fr" <?= ($_POST['client_language']??'fr')==='fr'?'selected':'' ?>>🇫🇷 Français</option>
        <option value="en" <?= ($_POST['client_language']??'')==='en'?'selected':'' ?>>🇬🇧 English</option>
        <option value="ar" <?= ($_POST['client_language']??'')==='ar'?'selected':'' ?>>🇲🇦 العربية</option>
      </select>
    </div>
  </div>
</div>

<!-- Section 3: Lieu -->
<div class="fk-card" id="hotel-fields">
  <div class="fk-card-header"><h3 class="fk-section-title">🏨 Informations hôtel</h3></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="fk-form-group">
      <label class="fk-label">Hôtel</label>
      <select name="hotel_id" class="fk-select">
        <option value="">— Sélectionner un hôtel —</option>
        <?php foreach ($hotels as $h): ?>
        <option value="<?= $h['id'] ?>" <?= ($_POST['hotel_id']??'')==$h['id']?'selected':'' ?>>
          <?= sanitize($h['name']) ?> (<?= sanitize($h['code']) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Numéro de chambre</label>
      <input type="text" name="room_number" class="fk-input" value="<?= sanitize($_POST['room_number']??'') ?>" placeholder="Ex: 312">
    </div>
  </div>
</div>

<div class="fk-card" id="city-fields" style="display:none">
  <div class="fk-card-header"><h3 class="fk-section-title">🏙️ Localisation City</h3></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="fk-form-group">
      <label class="fk-label">Ville</label>
      <input type="text" name="city" class="fk-input" value="<?= sanitize($_POST['city']??'Agadir') ?>" placeholder="Agadir">
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Quartier</label>
      <input type="text" name="area" class="fk-input" value="<?= sanitize($_POST['area']??'') ?>" placeholder="Quartier / Secteur">
    </div>
    <div class="fk-form-group" style="grid-column:span 2">
      <label class="fk-label">Adresse complète</label>
      <input type="text" name="address" class="fk-input" value="<?= sanitize($_POST['address']??'') ?>" placeholder="Rue, immeuble, numéro...">
    </div>
    <div class="fk-form-group" style="grid-column:span 2">
      <label class="fk-label">Notes localisation</label>
      <input type="text" name="location_notes" class="fk-input" value="<?= sanitize($_POST['location_notes']??'') ?>" placeholder="Indications pour trouver l'adresse">
    </div>
  </div>
</div>

<!-- Section 4: Service -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">Détails du service</h3></div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px">
    <div class="fk-form-group">
      <label class="fk-label">Date <span style="color:#EF4444">*</span></label>
      <input type="date" name="service_date" class="fk-input" value="<?= sanitize($_POST['service_date']??date('Y-m-d')) ?>" min="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Heure de début <span style="color:#EF4444">*</span></label>
      <input type="time" name="start_time" class="fk-input" value="<?= sanitize($_POST['start_time']??'10:00') ?>" required>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Durée</label>
      <select name="duration_minutes" class="fk-select">
        <?php
        $durations = [30=>'30 min',60=>'1h',90=>'1h30',120=>'2h',150=>'2h30',180=>'3h',240=>'4h',300=>'5h',360=>'6h',480=>'8h',600=>'10h',720=>'12h (journée)'];
        foreach ($durations as $mins => $label):
          $sel = (($_POST['duration_minutes']??120)==$mins)?'selected':'';
        ?>
        <option value="<?= $mins ?>" <?= $sel ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fk-form-group">
      <label class="fk-label">Nombre d'enfants</label>
      <select name="children_count" id="children_count" class="fk-select" onchange="generateChildForms(this.value)">
        <?php for ($n=1;$n<=10;$n++): ?>
        <option value="<?=$n?>" <?= (($_POST['children_count']??1)==$n)?'selected':'' ?>><?=$n?> enfant<?=$n>1?'s':''?></option>
        <?php endfor; ?>
      </select>
    </div>
  </div>
  <div class="fk-form-group" style="margin-top:8px">
    <label class="fk-label">Besoins particuliers (général)</label>
    <textarea name="special_needs" class="fk-input" rows="2" placeholder="Besoins généraux, allergies alimentaires communes..."><?= sanitize($_POST['special_needs']??'') ?></textarea>
  </div>
</div>

<!-- Section 5: Children -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">Détails des enfants</h3></div>
  <div id="children-container"></div>
</div>

</div><!-- /left col -->

<!-- RIGHT SIDEBAR -->
<div style="display:flex;flex-direction:column;gap:16px">

<!-- Tarification -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">💰 Tarification</h3></div>
  <div class="fk-form-group">
    <label class="fk-label">Tarif suggéré (DH)</label>
    <input type="number" name="suggested_price" class="fk-input" value="<?= sanitize($_POST['suggested_price']??'') ?>" placeholder="0" min="0" step="10">
  </div>
  <div class="fk-form-group">
    <label class="fk-label">Tarif final proposé (DH)</label>
    <input type="number" name="final_price" class="fk-input" value="<?= sanitize($_POST['final_price']??'') ?>" placeholder="0" min="0" step="10">
  </div>
  <div class="fk-form-group">
    <label class="fk-label">Statut du prix</label>
    <select name="price_status" class="fk-select">
      <option value="pending" selected>En attente</option>
      <option value="proposed">Proposé</option>
      <option value="confirmed">Confirmé</option>
      <option value="free">Gratuit</option>
    </select>
  </div>
  <div class="fk-alert fk-alert-info" style="font-size:12px;padding:10px 12px">
    Le tarif final est communiqué par Faiza après étude de la demande.
  </div>
</div>

<!-- Babysitter -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">👩 Babysitter</h3></div>
  <div class="fk-form-group">
    <label class="fk-label">Babysitter assignée</label>
    <select name="babysitter_id" class="fk-select">
      <option value="">— Attribution automatique —</option>
      <?php foreach ($babysitters as $bs): ?>
      <option value="<?= $bs['id'] ?>"><?= sanitize($bs['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="fk-form-group">
    <label class="fk-label">Nombre de babysitters recommandé</label>
    <select name="babysitters_count" class="fk-select">
      <?php for ($n=1;$n<=5;$n++): ?>
      <option value="<?=$n?>"><?=$n?></option>
      <?php endfor; ?>
    </select>
  </div>
</div>

<!-- Paiement -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">💳 Paiement</h3></div>
  <div class="fk-form-group">
    <label class="fk-label">Paiement requis ?</label>
    <div style="display:flex;gap:12px;margin-top:8px">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
        <input type="radio" name="payment_required" value="yes"> Oui
      </label>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
        <input type="radio" name="payment_required" value="no" checked> Non
      </label>
    </div>
  </div>
</div>

<!-- Notes internes -->
<div class="fk-card">
  <div class="fk-card-header"><h3 class="fk-section-title">📝 Notes internes</h3></div>
  <div class="fk-form-group">
    <textarea name="internal_notes" class="fk-input" rows="4" placeholder="Notes visibles par l'équipe Faiza uniquement..."><?= sanitize($_POST['internal_notes']??'') ?></textarea>
  </div>
</div>

<button type="submit" class="fk-btn fk-btn-primary" style="width:100%;padding:14px;font-size:15px">
  ✓ Créer la réservation
</button>

</div><!-- /right col -->
</div><!-- /grid -->
</form>

<style>
.fk-type-pill { display:flex; align-items:center; gap:8px; padding:10px 18px; border:2px solid #E5EDE9; border-radius:10px; cursor:pointer; font-size:14px; font-weight:500; transition:.2s; }
.fk-type-pill input { accent-color:#2D6A4F; }
.fk-type-pill:has(input:checked) { border-color:#2D6A4F; background:#F0FAF5; color:#2D6A4F; }
.fk-child-card { background:#F8FAF9; border:1px solid #E5EDE9; border-radius:10px; padding:16px; margin-bottom:12px; }
.fk-child-header { font-size:13px; font-weight:600; color:#2D6A4F; margin-bottom:10px; }
</style>

<script>
// Init type toggle
document.addEventListener('DOMContentLoaded', function() {
  const typeRadios = document.querySelectorAll('input[name="type"]');
  typeRadios.forEach(r => r.addEventListener('change', () => toggleType(r.value)));
  const checked = document.querySelector('input[name="type"]:checked');
  if (checked) toggleType(checked.value);
  generateChildForms(document.getElementById('children_count').value);
});

function toggleType(type) {
  document.getElementById('hotel-fields').style.display = type === 'hotel' ? 'block' : 'none';
  document.getElementById('city-fields').style.display  = type === 'city'  ? 'block' : 'none';
}

function generateChildForms(count) {
  const container = document.getElementById('children-container');
  container.innerHTML = '';
  for (let i = 0; i < parseInt(count); i++) {
    const num = i + 1;
    container.innerHTML += `
    <div class="fk-child-card">
      <div class="fk-child-header">Enfant #${num}</div>
      <div style="display:grid;grid-template-columns:1fr 80px 1fr;gap:10px;margin-bottom:8px">
        <div><label style="font-size:12px;color:#6B7A72;font-weight:500;display:block;margin-bottom:4px">Prénom</label>
          <input type="text" name="child_name[]" class="fk-input" placeholder="Prénom"></div>
        <div><label style="font-size:12px;color:#6B7A72;font-weight:500;display:block;margin-bottom:4px">Âge</label>
          <input type="number" name="child_age[]" class="fk-input" min="0" max="18" placeholder="ans"></div>
        <div><label style="font-size:12px;color:#6B7A72;font-weight:500;display:block;margin-bottom:4px">Allergies</label>
          <input type="text" name="child_allergies[]" class="fk-input" placeholder="Aucune / Préciser"></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div><label style="font-size:12px;color:#6B7A72;font-weight:500;display:block;margin-bottom:4px">Besoins particuliers</label>
          <input type="text" name="child_needs[]" class="fk-input" placeholder="Régime, mobilité..."></div>
        <div><label style="font-size:12px;color:#6B7A72;font-weight:500;display:block;margin-bottom:4px">Notes spéciales</label>
          <input type="text" name="child_notes[]" class="fk-input" placeholder="Informations supplémentaires"></div>
      </div>
    </div>`;
  }
}
</script>

<?php include 'layout-bottom.php'; ?>
