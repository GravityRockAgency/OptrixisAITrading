<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';
$lang  = $_GET['lang']  ?? 'fr';

if (!in_array($lang, ['fr','en','ar'])) $lang = 'fr';

$booking = null;
$error   = '';

if ($token) {
    $booking = db_fetch(
        "SELECT b.*, h.name as hotel_name FROM bookings b LEFT JOIN hotels h ON b.hotel_id=h.id WHERE b.secure_token=?",
        [$token]
    );
    if (!$booking) $error = 'token_invalid';
    elseif ($booking['payment_status'] === 'validated') $error = 'already_paid';
    elseif (in_array($booking['status'], ['cancelled','completed'])) $error = 'booking_closed';
} else {
    $error = 'no_token';
}

$uploaded = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $booking && !$error) {
    if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'file_required';
    } else {
        $upload_dir = defined('UPLOAD_PATH') ? UPLOAD_PATH . '/proofs/' : __DIR__ . '/../uploads/proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            file_put_contents($upload_dir . '.htaccess', "php_flag engine off\nOptions -ExecCGI\n<Files *>\n  SetHandler none\n</Files>");
        }
        $result = upload_file($_FILES['proof'], $upload_dir, ['image/jpeg','image/png','image/webp','application/pdf']);
        if ($result['success']) {
            $proof_url = (defined('UPLOAD_URL') ? UPLOAD_URL : '') . '/proofs/' . $result['filename'];
            db_query(
                "INSERT INTO payment_proofs (booking_id, filename, original_name, file_url, uploaded_by, notes, created_at)
                 VALUES (?, ?, ?, ?, 'client', ?, NOW())",
                [
                    $booking['id'],
                    $result['filename'],
                    $result['original_name'],
                    $proof_url,
                    sanitize($_POST['notes'] ?? '')
                ]
            );
            db_query("UPDATE bookings SET payment_status='proof_uploaded', updated_at=NOW() WHERE id=?", [$booking['id']]);
            log_activity('payment_proof_uploaded', 'Client a envoyé une preuve de paiement', $booking['id']);
            send_notification('payment_proof', [
                'booking_id'  => $booking['id'],
                'reference'   => $booking['reference'],
                'message'     => 'Preuve de paiement reçue pour la réservation ' . $booking['reference'],
            ]);
            $uploaded = true;
        } else {
            $upload_error = $result['error'];
        }
    }
}

$t = [
    'fr' => [
        'page_title'    => 'Paiement – Faiza Kids',
        'title'         => 'Envoyer votre preuve de paiement',
        'sub'           => 'Merci de télécharger votre reçu de virement bancaire pour finaliser votre réservation.',
        'ref_label'     => 'Réservation',
        'hotel_label'   => 'Hôtel / Lieu',
        'amount_label'  => 'Montant à régler',
        'amount_note'   => 'Montant communiqué par notre équipe',
        'bank_title'    => 'Coordonnées bancaires',
        'upload_title'  => 'Votre reçu de virement',
        'upload_hint'   => 'JPG, PNG, PDF ou WebP – max 8 MB',
        'notes_label'   => 'Remarques (optionnel)',
        'notes_ph'      => 'Ex: Virement effectué le 15/06 depuis CIH Bank',
        'submit'        => 'Envoyer la preuve',
        'success_title' => 'Preuve reçue !',
        'success_msg'   => 'Votre reçu a bien été reçu. Notre équipe va le vérifier et confirmer votre réservation dans les plus brefs délais.',
        'success_wa'    => 'Contacter notre équipe',
        'err_file'      => 'Veuillez sélectionner un fichier.',
        'err_invalid'   => 'Fichier invalide. Formats acceptés : JPG, PNG, PDF, WebP.',
        'err_size'      => 'Fichier trop volumineux (max 8 MB).',
        'err_token'     => 'Lien invalide ou expiré.',
        'err_paid'      => 'Ce paiement a déjà été validé.',
        'err_closed'    => 'Cette réservation est fermée.',
        'back'          => 'Retour',
    ],
    'en' => [
        'page_title'    => 'Payment – Faiza Kids',
        'title'         => 'Send your payment proof',
        'sub'           => 'Please upload your bank transfer receipt to finalize your booking.',
        'ref_label'     => 'Booking',
        'hotel_label'   => 'Hotel / Venue',
        'amount_label'  => 'Amount to pay',
        'amount_note'   => 'Amount communicated by our team',
        'bank_title'    => 'Bank details',
        'upload_title'  => 'Your transfer receipt',
        'upload_hint'   => 'JPG, PNG, PDF or WebP – max 8 MB',
        'notes_label'   => 'Notes (optional)',
        'notes_ph'      => 'E.g. Transfer sent on Jun 15 from CIH Bank',
        'submit'        => 'Send proof',
        'success_title' => 'Proof received!',
        'success_msg'   => 'Your receipt has been received. Our team will verify it and confirm your booking shortly.',
        'success_wa'    => 'Contact our team',
        'err_file'      => 'Please select a file.',
        'err_invalid'   => 'Invalid file. Accepted formats: JPG, PNG, PDF, WebP.',
        'err_size'      => 'File too large (max 8 MB).',
        'err_token'     => 'Invalid or expired link.',
        'err_paid'      => 'This payment has already been validated.',
        'err_closed'    => 'This booking is closed.',
        'back'          => 'Back',
    ],
    'ar' => [
        'page_title'    => 'الدفع – Faiza Kids',
        'title'         => 'إرسال إثبات الدفع',
        'sub'           => 'يرجى رفع وصل التحويل البنكي لإتمام حجزكم.',
        'ref_label'     => 'الحجز',
        'hotel_label'   => 'الفندق / المكان',
        'amount_label'  => 'المبلغ المطلوب',
        'amount_note'   => 'المبلغ المحدد من فريقنا',
        'bank_title'    => 'المعلومات البنكية',
        'upload_title'  => 'وصل التحويل',
        'upload_hint'   => 'JPG أو PNG أو PDF أو WebP – الحجم الأقصى 8 ميغا',
        'notes_label'   => 'ملاحظات (اختياري)',
        'notes_ph'      => 'مثال: تم التحويل بتاريخ 15 يونيو من بنك CIH',
        'submit'        => 'إرسال الإثبات',
        'success_title' => 'تم الاستلام!',
        'success_msg'   => 'تم استلام وصلكم. سيتحقق فريقنا منه ويؤكد حجزكم في أقرب وقت.',
        'success_wa'    => 'تواصل مع فريقنا',
        'err_file'      => 'يرجى اختيار ملف.',
        'err_invalid'   => 'ملف غير صالح. الصيغ المقبولة: JPG، PNG، PDF، WebP.',
        'err_size'      => 'الملف كبير جداً (الحجم الأقصى 8 ميغا).',
        'err_token'     => 'الرابط غير صالح أو منتهي الصلاحية.',
        'err_paid'      => 'تم التحقق من هذا الدفع مسبقاً.',
        'err_closed'    => 'هذا الحجز مغلق.',
        'back'          => 'رجوع',
    ],
][$lang] ?? [];

$admin_wa  = get_setting('admin_whatsapp', '+212600000000');
$bank_name = get_setting('bank_name', '');
$bank_rib  = get_setting('bank_rib', '');
$bank_iban = get_setting('bank_iban', '');
$bank_ben  = get_setting('bank_beneficiary', 'Faiza Multi Service');

$dir      = $lang === 'ar' ? 'rtl' : 'ltr';
$base_url = defined('BASE_URL') ? BASE_URL : '';

$wa_msg = "Bonjour, j'ai envoyé ma preuve de paiement pour la réservation {$booking['reference']}. Merci de confirmer.";
if ($lang === 'en') $wa_msg = "Hello, I sent my payment proof for booking {$booking['reference']}. Please confirm.";
if ($lang === 'ar') $wa_msg = "مرحباً، أرسلت إثبات دفعي للحجز {$booking['reference']}. شكراً للتأكيد.";
$wa_link = get_whatsapp_link($admin_wa, $wa_msg);

$upload_err_map = [
    'file_required' => $t['err_file'],
    'invalid_type'  => $t['err_invalid'],
    'file_too_large'=> $t['err_size'],
];
$upload_err_msg = $upload_err_map[$upload_error] ?? $upload_error;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sanitize($t['page_title']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_url ?>/assets/css/public.css">
<style>
body{font-family:'Inter',sans-serif;background:#F8FAF9;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:0}
.pp-wrap{max-width:560px;width:90%;margin:40px auto 60px}
.pp-logo{text-align:center;margin-bottom:28px}
.pp-logo-circle{width:48px;height:48px;border-radius:50%;background:#0D2B1D;color:#52B788;font-size:20px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 8px}
.pp-logo-name{font-size:14px;font-weight:700;color:#1A2E24}
.pp-card{background:#fff;border:1px solid #E5EDE9;border-radius:20px;padding:32px}
.pp-title{font-size:22px;font-weight:700;color:#1A2E24;margin-bottom:8px;text-align:center}
.pp-sub{font-size:15px;color:#6B7A72;text-align:center;margin-bottom:28px;line-height:1.6}
.pp-info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#F8FAF9;border-radius:8px;margin-bottom:8px}
.pp-info-label{font-size:12px;font-weight:600;color:#6B7A72;text-transform:uppercase;letter-spacing:.5px}
.pp-info-value{font-size:14px;font-weight:600;color:#1A2E24}
.pp-ref-value{font-size:16px;font-weight:700;color:#065F46;font-family:monospace}
.pp-section{margin:24px 0 0}
.pp-section-title{font-size:13px;font-weight:700;color:#1A2E24;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #E5EDE9;display:flex;align-items:center;gap:6px}
.bank-details{background:#F0FAF5;border:1px solid #A7F3D0;border-radius:12px;padding:16px}
.bank-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #D1FAE5;font-size:14px}
.bank-row:last-child{border-bottom:none}
.bank-key{color:#6B7A72;font-weight:500}
.bank-val{color:#065F46;font-weight:600;font-family:monospace;word-break:break-all;text-align:<?= $dir==='rtl'?'left':'right' ?>}
.pp-field{margin-bottom:16px}
.pp-label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
.pp-input,.pp-textarea{width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:10px;font-size:14px;font-family:inherit;color:#1A2E24;background:#fff;box-sizing:border-box;transition:.2s}
.pp-input:focus,.pp-textarea:focus{outline:none;border-color:#52B788;box-shadow:0 0 0 3px rgba(82,183,136,.12)}
.pp-textarea{resize:vertical;min-height:80px}
.upload-area{border:2px dashed #A7F3D0;border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:.2s;background:#F0FAF5;position:relative}
.upload-area:hover,.upload-area.drag{border-color:#059669;background:#ECFDF5}
.upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-icon{font-size:32px;margin-bottom:8px}
.upload-text{font-size:14px;color:#059669;font-weight:600}
.upload-hint{font-size:12px;color:#6B7A72;margin-top:4px}
.upload-preview{margin-top:12px;display:none}
.upload-preview img{max-width:100%;max-height:160px;border-radius:8px;border:1px solid #D1FAE5}
.pp-btn{display:block;width:100%;padding:15px;background:#059669;color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;margin-top:20px;font-family:inherit}
.pp-btn:hover{background:#047857}
.pp-btn:disabled{background:#9CA3AF;cursor:not-allowed}
.pp-alert-error{background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px 16px;color:#B91C1C;font-size:14px;margin-bottom:20px}
.pp-alert-error-block{background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:24px;text-align:center}
.pp-success-icon{width:72px;height:72px;border-radius:50%;background:#D1FAE5;color:#059669;font-size:36px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;animation:popIn .4s cubic-bezier(.2,1.6,.4,1)}
@keyframes popIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.success-card{text-align:center}
.success-title{font-size:22px;font-weight:700;color:#1A2E24;margin-bottom:8px}
.success-msg{font-size:15px;color:#6B7A72;line-height:1.6;margin-bottom:24px}
.wa-btn{display:block;padding:14px 24px;background:#25D366;color:#fff;border-radius:12px;text-decoration:none;font-size:15px;font-weight:600;margin-bottom:12px;transition:.2s;text-align:center}
.wa-btn:hover{background:#1DA851}
[dir="rtl"] .pp-info-row,[dir="rtl"] .bank-row{flex-direction:row-reverse}
[dir="rtl"] .pp-section-title{flex-direction:row-reverse}
</style>
</head>
<body>
<div class="pp-wrap">
  <div class="pp-logo">
    <div class="pp-logo-circle">F</div>
    <div class="pp-logo-name">Faiza Kids Concierge</div>
  </div>

  <?php if ($error): ?>
  <div class="pp-card">
    <div class="pp-alert-error-block">
      <div style="font-size:40px;margin-bottom:12px">⚠️</div>
      <div style="font-size:18px;font-weight:700;color:#1A2E24;margin-bottom:8px">
        <?php
        $err_msgs = ['token_invalid'=>$t['err_token'],'already_paid'=>$t['err_paid'],'booking_closed'=>$t['err_closed'],'no_token'=>$t['err_token']];
        echo sanitize($err_msgs[$error] ?? $error);
        ?>
      </div>
      <?php if ($admin_wa): ?>
      <a href="<?= htmlspecialchars(get_whatsapp_link($admin_wa, 'Bonjour, j\'ai besoin d\'aide pour mon paiement.')) ?>" target="_blank" class="wa-btn" style="margin-top:16px">
        💬 <?= sanitize($t['success_wa']) ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php elseif ($uploaded): ?>
  <div class="pp-card">
    <div class="success-card">
      <div class="pp-success-icon">✓</div>
      <div class="success-title"><?= sanitize($t['success_title']) ?></div>
      <div class="success-msg"><?= sanitize($t['success_msg']) ?></div>
      <?php if ($admin_wa): ?>
      <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank" class="wa-btn">
        💬 <?= sanitize($t['success_wa']) ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <div class="pp-card">
    <h1 class="pp-title"><?= sanitize($t['title']) ?></h1>
    <p class="pp-sub"><?= sanitize($t['sub']) ?></p>

    <!-- Booking info -->
    <div class="pp-info-row">
      <span class="pp-info-label"><?= sanitize($t['ref_label']) ?></span>
      <span class="pp-ref-value"><?= sanitize($booking['reference']) ?></span>
    </div>
    <?php if (!empty($booking['hotel_name'])): ?>
    <div class="pp-info-row">
      <span class="pp-info-label"><?= sanitize($t['hotel_label']) ?></span>
      <span class="pp-info-value"><?= sanitize($booking['hotel_name']) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($booking['price_amount'])): ?>
    <div class="pp-info-row">
      <span class="pp-info-label"><?= sanitize($t['amount_label']) ?></span>
      <span class="pp-info-value" style="color:#059669;font-size:17px"><?= format_price($booking['price_amount']) ?></span>
    </div>
    <?php else: ?>
    <div class="pp-info-row">
      <span class="pp-info-label"><?= sanitize($t['amount_label']) ?></span>
      <span class="pp-info-value" style="color:#6B7A72;font-style:italic"><?= sanitize($t['amount_note']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Bank details -->
    <?php if ($bank_name || $bank_rib || $bank_iban): ?>
    <div class="pp-section">
      <div class="pp-section-title">🏦 <?= sanitize($t['bank_title']) ?></div>
      <div class="bank-details">
        <?php if ($bank_ben): ?>
        <div class="bank-row"><span class="bank-key">Bénéficiaire</span><span class="bank-val"><?= sanitize($bank_ben) ?></span></div>
        <?php endif; ?>
        <?php if ($bank_name): ?>
        <div class="bank-row"><span class="bank-key">Banque</span><span class="bank-val"><?= sanitize($bank_name) ?></span></div>
        <?php endif; ?>
        <?php if ($bank_rib): ?>
        <div class="bank-row"><span class="bank-key">RIB</span><span class="bank-val"><?= sanitize($bank_rib) ?></span></div>
        <?php endif; ?>
        <?php if ($bank_iban): ?>
        <div class="bank-row"><span class="bank-key">IBAN</span><span class="bank-val"><?= sanitize($bank_iban) ?></span></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Upload form -->
    <div class="pp-section">
      <div class="pp-section-title">📎 <?= sanitize($t['upload_title']) ?></div>

      <?php if ($upload_err_msg): ?>
      <div class="pp-alert-error">⚠️ <?= sanitize($upload_err_msg) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="proofForm">
        <div class="pp-field">
          <div class="upload-area" id="uploadArea">
            <input type="file" name="proof" id="proofFile" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
            <div class="upload-icon">📂</div>
            <div class="upload-text"><?= sanitize($t['upload_hint']) ?></div>
            <div class="upload-hint" id="fileName"></div>
            <div class="upload-preview" id="uploadPreview"><img id="previewImg" src="" alt=""></div>
          </div>
        </div>

        <div class="pp-field">
          <label class="pp-label" for="notes"><?= sanitize($t['notes_label']) ?></label>
          <textarea class="pp-textarea" name="notes" id="notes" placeholder="<?= sanitize($t['notes_ph']) ?>"></textarea>
        </div>

        <button type="submit" class="pp-btn" id="submitBtn">
          📤 <?= sanitize($t['submit']) ?>
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
const proofFile = document.getElementById('proofFile');
const uploadArea = document.getElementById('uploadArea');
const fileName = document.getElementById('fileName');
const preview = document.getElementById('uploadPreview');
const previewImg = document.getElementById('previewImg');

if (proofFile) {
    proofFile.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        fileName.textContent = file.name + ' (' + (file.size/1024/1024).toFixed(2) + ' MB)';
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => { previewImg.src = e.target.result; preview.style.display='block'; };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });

    ['dragover','dragenter'].forEach(ev => uploadArea.addEventListener(ev, e => { e.preventDefault(); uploadArea.classList.add('drag'); }));
    ['dragleave','drop'].forEach(ev => uploadArea.addEventListener(ev, () => uploadArea.classList.remove('drag')));
}

const form = document.getElementById('proofForm');
if (form) {
    form.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Envoi en cours...'; }
    });
}
</script>
</body>
</html>
