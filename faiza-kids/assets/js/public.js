/**
 * FAIZA KIDS CONCIERGE — Public Booking Pages
 * public.js — Language switching, form logic, validation, submission
 */

'use strict';

/* =========================================================
   TRANSLATIONS
   ========================================================= */
const TRANSLATIONS = {
  fr: {
    child_n:           'Enfant',
    age_label:         'Âge',
    name_label:        'Prénom',
    notes_label:       'Allergies / Besoins spéciaux',
    age_placeholder:   '— Sélectionnez —',
    name_placeholder:  'Prénom de l\'enfant',
    notes_placeholder: 'Ex: allergie aux noix, asthme...',
    required:          'Ce champ est obligatoire',
    email_invalid:     'Adresse email invalide',
    phone_invalid:     'Numéro de téléphone invalide',
    date_advance:      'La réservation doit être effectuée au moins 24h à l\'avance',
    date_required:     'Veuillez sélectionner une date',
    children_required: 'Veuillez préciser le nombre d\'enfants',
    consent_required:  'Vous devez accepter les conditions',
    submit_loading:    'Envoi en cours...',
    submit_error:      'Une erreur est survenue. Veuillez réessayer.',
    copy_success:      'Référence copiée !',
    ages: ['1 an', '2 ans', '3 ans', '4 ans', '5 ans', '6 ans', '7 ans', '8 ans', '9 ans', '10 ans', '11 ans', '12 ans'],
  },
  en: {
    child_n:           'Child',
    age_label:         'Age',
    name_label:        'First name',
    notes_label:       'Allergies / Special needs',
    age_placeholder:   '— Select —',
    name_placeholder:  'Child\'s first name',
    notes_placeholder: 'E.g. nut allergy, asthma...',
    required:          'This field is required',
    email_invalid:     'Invalid email address',
    phone_invalid:     'Invalid phone number',
    date_advance:      'Booking must be made at least 24h in advance',
    date_required:     'Please select a date',
    children_required: 'Please specify the number of children',
    consent_required:  'You must accept the terms',
    submit_loading:    'Submitting...',
    submit_error:      'An error occurred. Please try again.',
    copy_success:      'Reference copied!',
    ages: ['1 year', '2 years', '3 years', '4 years', '5 years', '6 years', '7 years', '8 years', '9 years', '10 years', '11 years', '12 years'],
  },
  ar: {
    child_n:           'طفل',
    age_label:         'العمر',
    name_label:        'الاسم',
    notes_label:       'حساسية / احتياجات خاصة',
    age_placeholder:   '— اختر —',
    name_placeholder:  'اسم الطفل',
    notes_placeholder: 'مثال: حساسية من المكسرات، ربو...',
    required:          'هذا الحقل مطلوب',
    email_invalid:     'عنوان البريد الإلكتروني غير صالح',
    phone_invalid:     'رقم الهاتف غير صالح',
    date_advance:      'يجب الحجز قبل 24 ساعة على الأقل',
    date_required:     'يرجى اختيار تاريخ',
    children_required: 'يرجى تحديد عدد الأطفال',
    consent_required:  'يجب قبول الشروط',
    submit_loading:    'جارٍ الإرسال...',
    submit_error:      'حدث خطأ. يرجى المحاولة مرة أخرى.',
    copy_success:      'تم نسخ المرجع!',
    ages: ['سنة', 'سنتان', '3 سنوات', '4 سنوات', '5 سنوات', '6 سنوات', '7 سنوات', '8 سنوات', '9 سنوات', '10 سنوات', '11 سنة', '12 سنة'],
  },
};

/* =========================================================
   LANGUAGE MANAGER
   ========================================================= */
const LangManager = {
  current: 'fr',

  init() {
    // Detect from HTML lang attribute or URL param
    const urlParam = new URLSearchParams(window.location.search).get('lang');
    const htmlLang = document.documentElement.lang || 'fr';
    this.current = (urlParam || htmlLang || 'fr').slice(0, 2).toLowerCase();
    if (!TRANSLATIONS[this.current]) this.current = 'fr';

    this.applyLang(this.current);
    this.bindPills();
  },

  t(key) {
    return TRANSLATIONS[this.current]?.[key] || TRANSLATIONS.fr[key] || key;
  },

  applyLang(lang) {
    this.current = lang;

    // Set HTML dir and lang
    document.documentElement.lang = lang;
    document.documentElement.dir  = lang === 'ar' ? 'rtl' : 'ltr';

    // Update all translatable elements
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      const t   = TRANSLATIONS[lang]?.[key] || TRANSLATIONS.fr[key];
      if (t !== undefined) el.textContent = t;
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.dataset.i18nPlaceholder;
      const t   = TRANSLATIONS[lang]?.[key] || TRANSLATIONS.fr[key];
      if (t !== undefined) el.placeholder = t;
    });

    document.querySelectorAll('[data-i18n-href]').forEach(el => {
      const base = el.dataset.i18nHrefBase || el.getAttribute('href') || '';
      el.setAttribute('href', base.replace(/[?&]lang=[a-z]{2}/, '') + (base.includes('?') ? '&' : '?') + `lang=${lang}`);
    });

    // Persist in localStorage
    try { localStorage.setItem('fk_lang', lang); } catch {}
  },

  bindPills() {
    document.querySelectorAll('.fk-lang-pill').forEach(pill => {
      const lang = pill.dataset.lang;
      if (!lang) return;

      // Mark active
      pill.classList.toggle('active', lang === this.current);

      pill.addEventListener('click', e => {
        e.preventDefault();
        this.applyLang(lang);

        // Update URL
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        history.replaceState(null, '', url.toString());

        // Update active state
        document.querySelectorAll('.fk-lang-pill').forEach(p => {
          p.classList.toggle('active', p.dataset.lang === lang);
        });

        // Re-render children forms
        ChildrenForm.rerender();
      });
    });
  },
};

/* =========================================================
   CHILDREN FORM MANAGER
   ========================================================= */
const ChildrenForm = {
  container: null,
  countSelect: null,

  init() {
    this.container   = document.getElementById('fk-children-container');
    this.countSelect = document.querySelector('[name="children_count"]');

    if (!this.container || !this.countSelect) return;

    this.countSelect.addEventListener('change', () => {
      const count = parseInt(this.countSelect.value) || 0;
      this.render(count);
    });

    // Init on load
    const initCount = parseInt(this.countSelect.value) || 0;
    if (initCount > 0) this.render(initCount);
  },

  render(count) {
    const t = LangManager;
    this.container.innerHTML = '';

    for (let i = 1; i <= count; i++) {
      const card = document.createElement('div');
      card.className = 'fk-child-card';
      card.dataset.childIndex = i;

      const ageOptions = (TRANSLATIONS[t.current]?.ages || TRANSLATIONS.fr.ages)
        .map((label, idx) => `<option value="${idx + 1}">${label}</option>`)
        .join('');

      card.innerHTML = `
        <div class="fk-child-card-header">
          <div class="fk-child-card-title">
            <span class="fk-child-num">${i}</span>
            <span>${t.t('child_n')} ${i}</span>
          </div>
        </div>
        <div class="fk-booking-field-row">
          <div class="fk-booking-field">
            <label class="fk-booking-label">${t.t('name_label')}</label>
            <input
              type="text"
              name="children[${i}][name]"
              class="fk-booking-input"
              placeholder="${t.t('name_placeholder')}"
              autocomplete="given-name"
            >
          </div>
          <div class="fk-booking-field">
            <label class="fk-booking-label">${t.t('age_label')} <span class="required">*</span></label>
            <select name="children[${i}][age]" class="fk-booking-select" required>
              <option value="">${t.t('age_placeholder')}</option>
              ${ageOptions}
            </select>
          </div>
        </div>
        <div class="fk-booking-field" style="margin-top:10px">
          <label class="fk-booking-label">${t.t('notes_label')}</label>
          <input
            type="text"
            name="children[${i}][notes]"
            class="fk-booking-input"
            placeholder="${t.t('notes_placeholder')}"
          >
        </div>
      `;

      this.container.appendChild(card);
    }
  },

  rerender() {
    if (!this.countSelect) return;
    const count = parseInt(this.countSelect.value) || 0;
    if (count > 0) this.render(count);
  },

  getData() {
    const data = [];
    if (!this.container) return data;
    this.container.querySelectorAll('.fk-child-card').forEach((card, i) => {
      data.push({
        name:  card.querySelector(`[name="children[${i+1}][name]"]`)?.value || '',
        age:   card.querySelector(`[name="children[${i+1}][age]"]`)?.value || '',
        notes: card.querySelector(`[name="children[${i+1}][notes]"]`)?.value || '',
      });
    });
    return data;
  },
};

/* =========================================================
   FORM VALIDATOR
   ========================================================= */
const FormValidator = {
  validate(form) {
    let valid = true;
    this.clearAll(form);

    const fields = form.querySelectorAll('[required], [data-validate]');
    fields.forEach(field => {
      const val = field.value.trim();

      if (field.required && !val) {
        this.showError(field, LangManager.t('required'));
        valid = false;
        return;
      }

      if (!val) return;

      if (field.type === 'email') {
        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(val)) {
          this.showError(field, LangManager.t('email_invalid'));
          valid = false;
          return;
        }
      }

      if (field.type === 'tel' || field.dataset.validate === 'phone') {
        const phoneRe = /^\+?[\d\s\-().]{8,}$/;
        if (!phoneRe.test(val)) {
          this.showError(field, LangManager.t('phone_invalid'));
          valid = false;
          return;
        }
      }

      if (field.type === 'datetime-local' || field.dataset.validate === 'date-24h') {
        const selected = new Date(val);
        const minTime  = new Date();
        minTime.setHours(minTime.getHours() + 24);
        if (selected < minTime) {
          this.showError(field, LangManager.t('date_advance'));
          valid = false;
          return;
        }
      }
    });

    // Children ages
    form.querySelectorAll('[name$="[age]"][required]').forEach(sel => {
      if (!sel.value) {
        this.showError(sel, LangManager.t('required'));
        valid = false;
      }
    });

    // Consent checkbox
    const consent = form.querySelector('[name="consent"]');
    if (consent && !consent.checked) {
      this.showError(consent, LangManager.t('consent_required'));
      valid = false;
    }

    return valid;
  },

  showError(field, msg) {
    field.classList.add('error');

    let errEl = field.parentElement.querySelector('.fk-field-error');
    if (!errEl) {
      errEl = document.createElement('div');
      errEl.className = 'fk-field-error';
      field.parentElement.appendChild(errEl);
    }
    errEl.textContent = msg;
    errEl.classList.add('show');

    // Scroll to first error
    if (!document.querySelector('.fk-booking-input.error.scrolled-to, .fk-booking-select.error.scrolled-to')) {
      field.classList.add('scrolled-to');
      field.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => field.classList.remove('scrolled-to'), 1000);
    }
  },

  clearAll(form) {
    form.querySelectorAll('.fk-field-error').forEach(e => {
      e.textContent = '';
      e.classList.remove('show');
    });
    form.querySelectorAll('.error').forEach(f => f.classList.remove('error'));
  },

  clearField(field) {
    field.classList.remove('error');
    const errEl = field.parentElement.querySelector('.fk-field-error');
    if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
  },
};

/* =========================================================
   DATE VALIDATION
   ========================================================= */
function initDateValidation() {
  const dateFields = document.querySelectorAll('input[type="datetime-local"], input[data-validate="date-24h"]');

  dateFields.forEach(field => {
    // Set minimum
    const now = new Date();
    now.setHours(now.getHours() + 24);
    field.min = now.toISOString().slice(0, 16);

    field.addEventListener('change', () => {
      const selected = new Date(field.value);
      const minTime  = new Date();
      minTime.setHours(minTime.getHours() + 24);

      if (selected < minTime) {
        FormValidator.showError(field, LangManager.t('date_advance'));
        field.value = '';
      } else {
        FormValidator.clearField(field);
      }
    });

    field.addEventListener('input', () => {
      if (field.classList.contains('error') && field.value) {
        FormValidator.clearField(field);
      }
    });
  });
}

/* =========================================================
   REAL-TIME FIELD VALIDATION
   ========================================================= */
function initRealtimeValidation() {
  document.querySelectorAll('.fk-booking-input[required], .fk-booking-select[required]').forEach(field => {
    field.addEventListener('blur', () => {
      FormValidator.clearField(field);
      if (!field.value.trim()) {
        FormValidator.showError(field, LangManager.t('required'));
      }
    });

    field.addEventListener('input', () => {
      if (field.classList.contains('error') && field.value.trim()) {
        FormValidator.clearField(field);
      }
    });
  });
}

/* =========================================================
   FORM SUBMISSION
   ========================================================= */
function initFormSubmit() {
  const form = document.getElementById('fk-booking-form');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();

    if (!FormValidator.validate(form)) return;

    const submitBtn = form.querySelector('.fk-submit-btn');
    const origText  = submitBtn?.innerHTML || '';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = LangManager.t('submit_loading');
      submitBtn.classList.add('loading');
    }

    try {
      const formData = new FormData(form);

      // Append children data if collected separately
      const childData = ChildrenForm.getData();
      if (childData.length) {
        formData.set('children_json', JSON.stringify(childData));
      }

      const action = form.action || window.location.href;

      const res = await fetch(action, {
        method: 'POST',
        body:   formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      const data = await res.json();

      if (data.success) {
        showSuccessState(data);
      } else {
        // Show server-side errors
        if (data.errors && typeof data.errors === 'object') {
          Object.entries(data.errors).forEach(([fieldName, msg]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) FormValidator.showError(field, msg);
          });
        } else {
          showFormError(data.message || LangManager.t('submit_error'));
        }
      }
    } catch (err) {
      console.error('[FK Public] Submit error:', err);
      showFormError(LangManager.t('submit_error'));
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
        submitBtn.classList.remove('loading');
      }
    }
  });
}

function showSuccessState(data) {
  const formSection = document.getElementById('fk-form-section');
  const successSection = document.getElementById('fk-success-section');

  if (successSection) {
    // Fill in reference number
    const refEl = successSection.querySelector('[data-ref]');
    if (refEl && data.reference) refEl.textContent = data.reference;

    // Add copy listener
    const copyBtn = successSection.querySelector('[data-copy-ref]');
    if (copyBtn && data.reference) {
      copyBtn.addEventListener('click', () => {
        navigator.clipboard?.writeText(data.reference).then(() => {
          copyBtn.textContent = LangManager.t('copy_success');
          setTimeout(() => { copyBtn.textContent = data.reference; }, 2000);
        }).catch(() => {});
      });
    }

    if (formSection)    formSection.style.display = 'none';
    successSection.style.display = 'block';
    successSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  } else {
    // Fallback: redirect
    if (data.redirect) {
      window.location.href = data.redirect;
    }
  }
}

function showFormError(message) {
  let alertEl = document.getElementById('fk-form-error-alert');
  if (!alertEl) {
    alertEl = document.createElement('div');
    alertEl.id = 'fk-form-error-alert';
    alertEl.className = 'fk-pub-alert fk-pub-alert-error';
    const form = document.getElementById('fk-booking-form');
    if (form) form.insertAdjacentElement('beforebegin', alertEl);
  }
  alertEl.textContent = message;
  alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

  setTimeout(() => {
    if (alertEl.parentElement) alertEl.remove();
  }, 6000);
}

/* =========================================================
   RTL / LTR TOGGLE
   ========================================================= */
function applyRTL(isRTL) {
  document.documentElement.dir = isRTL ? 'rtl' : 'ltr';

  // Font adjustments for Arabic
  if (isRTL) {
    document.documentElement.style.setProperty('--pub-font', "'Noto Sans Arabic', 'Inter', sans-serif");
    // Inject Arabic font if not present
    if (!document.getElementById('fk-arabic-font')) {
      const link = document.createElement('link');
      link.id   = 'fk-arabic-font';
      link.rel  = 'stylesheet';
      link.href = 'https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap';
      document.head.appendChild(link);
    }
  }
}

/* =========================================================
   COPY REFERENCE
   ========================================================= */
function initCopyReference() {
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.dataset.copy ||
                   document.querySelector(btn.dataset.copyTarget)?.textContent?.trim();
      if (!text) return;

      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          const orig = btn.innerHTML;
          btn.textContent = LangManager.t('copy_success');
          setTimeout(() => { btn.innerHTML = orig; }, 2200);
        });
      }
    });
  });
}

/* =========================================================
   PROGRESS INDICATOR (multi-section form)
   ========================================================= */
function initFormProgress() {
  const sections = document.querySelectorAll('.fk-booking-section');
  const progress = document.getElementById('fk-form-progress');

  if (!sections.length || !progress) return;

  const bar = progress.querySelector('.fk-progress-bar');

  function updateProgress() {
    let filled = 0;
    let total  = 0;

    sections.forEach(section => {
      const fields = section.querySelectorAll('[required]');
      fields.forEach(f => {
        total++;
        if (f.value.trim()) filled++;
      });
    });

    const pct = total > 0 ? Math.round((filled / total) * 100) : 0;
    if (bar) bar.style.width = `${pct}%`;

    const label = progress.querySelector('[data-progress-label]');
    if (label) label.textContent = `${pct}%`;
  }

  document.querySelectorAll('.fk-booking-input, .fk-booking-select').forEach(field => {
    field.addEventListener('input',  updateProgress);
    field.addEventListener('change', updateProgress);
  });

  // Use MutationObserver to detect dynamically added children fields
  const childContainer = document.getElementById('fk-children-container');
  if (childContainer) {
    const observer = new MutationObserver(() => {
      document.querySelectorAll('.fk-booking-input, .fk-booking-select').forEach(f => {
        f.removeEventListener('input',  updateProgress);
        f.removeEventListener('change', updateProgress);
        f.addEventListener('input',  updateProgress);
        f.addEventListener('change', updateProgress);
      });
      updateProgress();
    });
    observer.observe(childContainer, { childList: true, subtree: true });
  }

  updateProgress();
}

/* =========================================================
   SUMMARY LIVE UPDATE
   ========================================================= */
function initBookingSummary() {
  const summary    = document.getElementById('fk-booking-summary');
  const dateInput  = document.querySelector('[name="booking_date"], [name="date"]');
  const timeInput  = document.querySelector('[name="start_time"], [name="time"]');
  const durationSel = document.querySelector('[name="duration"]');
  const countSel   = document.querySelector('[name="children_count"]');

  if (!summary) return;

  function updateSummary() {
    const date     = dateInput?.value;
    const time     = timeInput?.value;
    const duration = durationSel?.value;
    const count    = countSel?.value;

    if (!date && !time && !count) {
      summary.classList.remove('show');
      return;
    }

    summary.classList.add('show');

    const dateEl     = summary.querySelector('[data-sum-date]');
    const timeEl     = summary.querySelector('[data-sum-time]');
    const durationEl = summary.querySelector('[data-sum-duration]');
    const countEl    = summary.querySelector('[data-sum-count]');

    if (dateEl && date) {
      const d = new Date(date);
      dateEl.textContent = isNaN(d) ? date : d.toLocaleDateString('fr-MA', {
        weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
      });
    }

    if (timeEl && time) timeEl.textContent = time;
    if (durationEl && duration) durationEl.textContent = `${duration}h`;
    if (countEl && count) countEl.textContent = `${count} enfant(s)`;
  }

  [dateInput, timeInput, durationSel, countSel].forEach(el => {
    if (el) el.addEventListener('change', updateSummary);
  });
}

/* =========================================================
   HOTEL-SPECIFIC COLORS
   ========================================================= */
function applyHotelTheme() {
  const meta = document.querySelector('meta[name="hotel-color"]');
  if (!meta) return;

  const color = meta.content;
  if (color && /^#[0-9A-Fa-f]{6}$/.test(color)) {
    document.documentElement.style.setProperty('--pub-primary', color);
    // Derive lighter version
    document.documentElement.style.setProperty('--pub-primary-dark', adjustColor(color, -20));
  }
}

function adjustColor(hex, amount) {
  const num  = parseInt(hex.replace('#', ''), 16);
  const r    = Math.min(255, Math.max(0, (num >> 16) + amount));
  const g    = Math.min(255, Math.max(0, ((num >> 8) & 0xFF) + amount));
  const b    = Math.min(255, Math.max(0, (num & 0xFF) + amount));
  return '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('');
}

/* =========================================================
   INIT
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  LangManager.init();
  ChildrenForm.init();
  initDateValidation();
  initRealtimeValidation();
  initFormSubmit();
  initCopyReference();
  initFormProgress();
  initBookingSummary();
  applyHotelTheme();

  // Apply RTL if Arabic
  if (LangManager.current === 'ar') applyRTL(true);
});

/* =========================================================
   EXPOSE
   ========================================================= */
window.FKPublic = {
  LangManager,
  ChildrenForm,
  FormValidator,
};
