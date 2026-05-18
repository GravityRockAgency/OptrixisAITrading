/* =============================================================
   FAIZA KIDS CONCIERGE — Public Booking JS
   Version 2.0
   ============================================================= */

(function () {
  'use strict';

  // ── Child form generation ──────────────────────────────────────
  window.generateChildForms = function (count, lang) {
    const container = document.getElementById('childrenContainer');
    if (!container) return;

    const L = lang || window.FKPUB_LANG || 'fr';
    const labels = {
      fr: { child: 'Enfant', name: 'Prénom', age: 'Âge', allergies: 'Allergies', needs: 'Besoins particuliers', notes: 'Notes spéciales' },
      en: { child: 'Child',  name: 'Name',   age: 'Age', allergies: 'Allergies', needs: 'Special needs',       notes: 'Special notes'  },
      ar: { child: 'الطفل',  name: 'الاسم', age: 'العمر', allergies: 'الحساسية', needs: 'احتياجات خاصة',      notes: 'ملاحظات'         },
    };
    const t = labels[L] || labels.fr;
    const n = Math.max(1, parseInt(count, 10) || 1);

    let html = '';
    for (let i = 0; i < n; i++) {
      html += `
      <div class="pk-child">
        <div class="pk-child-head">
          <div class="pk-child-num">${i + 1}</div>
          <span>${t.child} ${i + 1}</span>
        </div>
        <div class="pk-grid-4">
          <div class="pk-field pk-col-2">
            <label class="pk-label">${t.name}</label>
            <input type="text" name="child_name[]" class="pk-input" placeholder="${t.name}">
          </div>
          <div class="pk-field">
            <label class="pk-label">${t.age}</label>
            <input type="number" name="child_age[]" class="pk-input" min="0" max="17" placeholder="ans">
          </div>
          <div class="pk-field">
            <label class="pk-label">${t.allergies}</label>
            <input type="text" name="child_allergies[]" class="pk-input" placeholder="Aucune">
          </div>
          <div class="pk-field pk-col-2">
            <label class="pk-label">${t.needs}</label>
            <input type="text" name="child_needs[]" class="pk-input">
          </div>
          <div class="pk-field pk-col-2">
            <label class="pk-label">${t.notes}</label>
            <input type="text" name="child_notes[]" class="pk-input">
          </div>
        </div>
      </div>`;
    }
    container.innerHTML = html;
  };

  // ── Form submit protection ─────────────────────────────────────
  function protectSubmit(form) {
    if (!form) return;
    form.addEventListener('submit', function (e) {
      const btn = form.querySelector('.pk-submit-btn');
      if (!btn) return;

      // Basic client-side validation
      let valid = true;
      const requiredInputs = form.querySelectorAll('[required]');
      requiredInputs.forEach(function (inp) {
        inp.classList.remove('is-error');
        if (!inp.value.trim()) {
          inp.classList.add('is-error');
          valid = false;
        }
      });

      if (!valid) {
        e.preventDefault();
        // Scroll to first error
        const first = form.querySelector('.is-error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      // Prevent double submit
      if (btn.disabled) { e.preventDefault(); return; }
      btn.disabled = true;
      btn.classList.add('is-loading');
      const originalText = btn.textContent;
      btn.textContent = '';
    });
  }

  // ── Copy to clipboard ──────────────────────────────────────────
  window.copyRef = function (text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text)
      .then(function () {
        const orig = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function () { btn.textContent = orig; }, 2000);
      })
      .catch(function () {
        // fallback
        const el = document.createElement('textarea');
        el.value = text;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        const orig = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function () { btn.textContent = orig; }, 2000);
      });
  };

  // ── Init ───────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    // Init child forms
    const countSel = document.getElementById('childCount');
    if (countSel) {
      const initCount = parseInt(countSel.value, 10) || 1;
      generateChildForms(initCount);
      countSel.addEventListener('change', function () {
        generateChildForms(this.value);
      });
    }

    // Protect forms
    protectSubmit(document.getElementById('bookingForm'));
    protectSubmit(document.getElementById('cityBookingForm'));

    // Clear error state on input
    document.addEventListener('input', function (e) {
      if (e.target.classList.contains('is-error')) {
        e.target.classList.remove('is-error');
      }
    });
  });

}());
