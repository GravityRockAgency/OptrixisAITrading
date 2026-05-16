/**
 * FAIZA KIDS CONCIERGE — Admin Panel JavaScript
 * app.js — Complete admin functionality
 */

'use strict';

/* =========================================================
   CORE NAMESPACE & API UTILITIES
   ========================================================= */
const FK = {

  /* -------------------------------------------------------
     API helpers
     ------------------------------------------------------- */
  async get(url) {
    try {
      const res = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error('[FK.get]', err);
      throw err;
    }
  },

  async post(url, data = {}) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error('[FK.post]', err);
      throw err;
    }
  },

  async postForm(url, formData) {
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: formData,
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error('[FK.postForm]', err);
      throw err;
    }
  },

  /* -------------------------------------------------------
     Toast notifications
     ------------------------------------------------------- */
  toast(message, type = 'success', duration = 3500) {
    const container = FK._getToastContainer();

    const icons = {
      success: '✓',
      error:   '✕',
      warning: '⚠',
      info:    'ℹ',
    };

    const toast = document.createElement('div');
    toast.className = `fk-toast fk-toast-${type}`;
    toast.innerHTML = `
      <span class="fk-toast-icon">${icons[type] || icons.info}</span>
      <span class="fk-toast-text">${FK._escapeHtml(message)}</span>
    `;

    container.appendChild(toast);

    toast.addEventListener('click', () => FK._removeToast(toast));

    setTimeout(() => FK._removeToast(toast), duration);
  },

  _removeToast(toast) {
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 250);
  },

  _getToastContainer() {
    let c = document.getElementById('fk-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'fk-toast-container';
      c.className = 'fk-toast-container';
      document.body.appendChild(c);
    }
    return c;
  },

  /* -------------------------------------------------------
     Modal management
     ------------------------------------------------------- */
  modal: {
    open(id) {
      const overlay = document.getElementById(id);
      if (!overlay) return;
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
      // Focus first focusable element
      const focusable = overlay.querySelector(
        'input, button, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (focusable) setTimeout(() => focusable.focus(), 100);
    },

    close(id) {
      const overlay = typeof id === 'string'
        ? document.getElementById(id)
        : id;
      if (!overlay) return;
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    },

    closeAll() {
      document.querySelectorAll('.fk-modal-overlay.open').forEach(m => {
        FK.modal.close(m);
      });
    },

    confirm(message, onConfirm, options = {}) {
      const title  = options.title  || 'Confirmer';
      const label  = options.label  || 'Confirmer';
      const type   = options.type   || 'danger';
      const detail = options.detail || '';

      let overlay = document.getElementById('fk-confirm-overlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'fk-confirm-overlay';
        overlay.className = 'fk-modal-overlay fk-confirm-modal';
        overlay.innerHTML = `
          <div class="fk-modal fk-modal-sm">
            <div class="fk-modal-body fk-text-center" style="padding:32px 24px">
              <div class="fk-confirm-icon fk-confirm-icon-${type}" id="fk-confirm-icon" style="margin:0 auto 16px">⚠</div>
              <div class="fk-confirm-title" id="fk-confirm-title"></div>
              <div class="fk-confirm-text" id="fk-confirm-text"></div>
              ${detail ? `<div class="fk-text-sm fk-text-muted fk-mt-3" id="fk-confirm-detail"></div>` : ''}
            </div>
            <div class="fk-modal-footer" style="justify-content:center;gap:10px;padding:16px 24px;border-top:1px solid var(--fk-border)">
              <button class="fk-btn fk-btn-secondary" id="fk-confirm-cancel">Annuler</button>
              <button class="fk-btn fk-btn-${type}" id="fk-confirm-ok"></button>
            </div>
          </div>
        `;
        document.body.appendChild(overlay);
      }

      overlay.querySelector('#fk-confirm-title').textContent = title;
      overlay.querySelector('#fk-confirm-text').textContent  = message;
      overlay.querySelector('#fk-confirm-ok').textContent    = label;
      if (detail) {
        const d = overlay.querySelector('#fk-confirm-detail');
        if (d) d.textContent = detail;
      }

      const cancelBtn = overlay.querySelector('#fk-confirm-cancel');
      const okBtn     = overlay.querySelector('#fk-confirm-ok');

      // Clone to remove old listeners
      const newOk     = okBtn.cloneNode(true);
      const newCancel = cancelBtn.cloneNode(true);
      okBtn.replaceWith(newOk);
      cancelBtn.replaceWith(newCancel);

      newOk.textContent = label;

      newOk.addEventListener('click', () => {
        FK.modal.close('fk-confirm-overlay');
        if (typeof onConfirm === 'function') onConfirm();
      });

      newCancel.addEventListener('click', () => {
        FK.modal.close('fk-confirm-overlay');
      });

      FK.modal.open('fk-confirm-overlay');
    },
  },

  /* -------------------------------------------------------
     Formatting utilities
     ------------------------------------------------------- */
  formatDate(date, opts = {}) {
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d)) return '—';
    const defaultOpts = {
      day:   '2-digit',
      month: '2-digit',
      year:  'numeric',
      ...opts,
    };
    return d.toLocaleDateString('fr-MA', defaultOpts);
  },

  formatDateTime(date) {
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d)) return '—';
    return d.toLocaleDateString('fr-MA', {
      day:    '2-digit',
      month:  '2-digit',
      year:   'numeric',
      hour:   '2-digit',
      minute: '2-digit',
    });
  },

  formatPrice(amount, currency = 'MAD') {
    if (amount === null || amount === undefined || isNaN(amount)) return '—';
    const n = parseFloat(amount);
    return new Intl.NumberFormat('fr-MA', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(n) + ' ' + currency;
  },

  formatDuration(minutes) {
    if (!minutes || isNaN(minutes)) return '—';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m}min`;
    if (m === 0) return `${h}h`;
    return `${h}h${String(m).padStart(2, '0')}`;
  },

  timeAgo(date) {
    const d   = date instanceof Date ? date : new Date(date);
    const now = new Date();
    const sec = Math.floor((now - d) / 1000);
    if (sec < 60)   return 'À l\'instant';
    if (sec < 3600) return `Il y a ${Math.floor(sec/60)}min`;
    if (sec < 86400) return `Il y a ${Math.floor(sec/3600)}h`;
    if (sec < 604800) return `Il y a ${Math.floor(sec/86400)}j`;
    return FK.formatDate(d);
  },

  copyToClipboard(text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        FK.toast('Copié dans le presse-papiers', 'success', 2000);
      }).catch(() => FK._legacyCopy(text));
    } else {
      FK._legacyCopy(text);
    }
  },

  _legacyCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      FK.toast('Copié dans le presse-papiers', 'success', 2000);
    } catch {
      FK.toast('Impossible de copier', 'error');
    }
    ta.remove();
  },

  _escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  },

  /* -------------------------------------------------------
     Form helpers
     ------------------------------------------------------- */
  serializeForm(form) {
    const data = {};
    new FormData(form).forEach((val, key) => {
      if (data[key] !== undefined) {
        if (!Array.isArray(data[key])) data[key] = [data[key]];
        data[key].push(val);
      } else {
        data[key] = val;
      }
    });
    return data;
  },

  validateForm(form) {
    let valid = true;
    const fields = form.querySelectorAll('[required]');

    fields.forEach(field => {
      FK._clearFieldError(field);
      const val = field.value.trim();

      if (!val) {
        FK._showFieldError(field, 'Ce champ est obligatoire');
        valid = false;
        return;
      }

      if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        FK._showFieldError(field, 'Adresse email invalide');
        valid = false;
        return;
      }

      if (field.type === 'tel' && !/^\+?[\d\s\-()]{8,}$/.test(val)) {
        FK._showFieldError(field, 'Numéro de téléphone invalide');
        valid = false;
        return;
      }

      if (field.dataset.min && parseFloat(val) < parseFloat(field.dataset.min)) {
        FK._showFieldError(field, `Valeur minimum: ${field.dataset.min}`);
        valid = false;
      }
    });

    return valid;
  },

  _showFieldError(field, msg) {
    field.classList.add('error');
    const existing = field.parentElement.querySelector('.fk-form-error');
    if (!existing) {
      const err = document.createElement('div');
      err.className = 'fk-form-error';
      err.textContent = msg;
      field.parentElement.appendChild(err);
    }
  },

  _clearFieldError(field) {
    field.classList.remove('error');
    const err = field.parentElement.querySelector('.fk-form-error');
    if (err) err.remove();
  },

  resetForm(form) {
    form.reset();
    form.querySelectorAll('.fk-form-error').forEach(e => e.remove());
    form.querySelectorAll('.error').forEach(f => f.classList.remove('error'));
  },

  /* -------------------------------------------------------
     Loading states
     ------------------------------------------------------- */
  setLoading(btn, loading) {
    if (typeof btn === 'string') btn = document.getElementById(btn);
    if (!btn) return;

    if (loading) {
      btn.dataset.origText = btn.innerHTML;
      btn.classList.add('loading');
      btn.disabled = true;
    } else {
      btn.classList.remove('loading');
      btn.disabled = false;
      if (btn.dataset.origText) {
        btn.innerHTML = btn.dataset.origText;
        delete btn.dataset.origText;
      }
    }
  },

  showSkeleton(container) {
    if (typeof container === 'string') container = document.getElementById(container);
    if (!container) return;
    container.dataset.origContent = container.innerHTML;
    container.innerHTML = `
      <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
        <div class="fk-skeleton fk-skeleton-title"></div>
        <div class="fk-skeleton fk-skeleton-text"></div>
        <div class="fk-skeleton fk-skeleton-text" style="width:80%"></div>
        <div class="fk-skeleton fk-skeleton-text" style="width:60%"></div>
      </div>
    `;
  },

  hideSkeleton(container) {
    if (typeof container === 'string') container = document.getElementById(container);
    if (!container) return;
    if (container.dataset.origContent) {
      container.innerHTML = container.dataset.origContent;
      delete container.dataset.origContent;
    }
  },

  /* -------------------------------------------------------
     Debounce
     ------------------------------------------------------- */
  debounce(fn, delay) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  },
};

/* =========================================================
   SIDEBAR TOGGLE
   ========================================================= */
function initSidebar() {
  const hamburger = document.querySelector('.fk-hamburger');
  const sidebar   = document.querySelector('.fk-sidebar');
  let overlay     = document.querySelector('.fk-sidebar-overlay');

  if (!hamburger || !sidebar) return;

  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'fk-sidebar-overlay';
    document.body.appendChild(overlay);
  }

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  overlay.addEventListener('click', closeSidebar);

  // Close on ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
  });
}

/* =========================================================
   ACTIVE NAV ITEM
   ========================================================= */
function initActiveNav() {
  const currentPath = window.location.pathname;
  const links = document.querySelectorAll('.fk-sidebar-nav a');

  links.forEach(link => {
    const href = link.getAttribute('href');
    if (!href) return;
    const linkPath = new URL(href, window.location.origin).pathname;
    if (
      currentPath === linkPath ||
      (linkPath !== '/' && currentPath.startsWith(linkPath))
    ) {
      link.classList.add('active');
    }
  });
}

/* =========================================================
   GLOBAL SEARCH
   ========================================================= */
function initSearch() {
  const searchInput   = document.querySelector('.fk-search-input');
  const searchResults = document.querySelector('.fk-search-results');

  if (!searchInput || !searchResults) return;

  const doSearch = FK.debounce(async (query) => {
    if (query.length < 2) {
      searchResults.classList.remove('open');
      return;
    }

    try {
      const data = await FK.get(`/admin/api/search.php?q=${encodeURIComponent(query)}`);
      renderSearchResults(data.results || []);
    } catch {
      // Silently fail search
    }
  }, 300);

  function renderSearchResults(results) {
    if (!results.length) {
      searchResults.innerHTML = `
        <div class="fk-search-result-item" style="color:var(--fk-text-muted)">
          Aucun résultat pour cette recherche
        </div>
      `;
    } else {
      searchResults.innerHTML = results.map(r => `
        <a href="${FK._escapeHtml(r.url || '#')}" class="fk-search-result-item">
          <span style="color:var(--fk-text-muted);font-size:12px">${FK._escapeHtml(r.type || '')}</span>
          <span>${FK._escapeHtml(r.label)}</span>
        </a>
      `).join('');
    }
    searchResults.classList.add('open');
  }

  searchInput.addEventListener('input', e => doSearch(e.target.value.trim()));

  searchInput.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      searchResults.classList.remove('open');
      searchInput.blur();
    }
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.fk-search')) {
      searchResults.classList.remove('open');
    }
  });
}

/* =========================================================
   NOTIFICATIONS BELL
   ========================================================= */
function initNotifications() {
  const bell  = document.querySelector('.fk-notif-btn');
  const panel = document.querySelector('.fk-notif-panel');

  if (!bell || !panel) return;

  bell.addEventListener('click', async (e) => {
    e.stopPropagation();
    const isOpen = panel.classList.contains('open');

    document.querySelectorAll('.fk-dropdown-menu.open, .fk-notif-panel.open').forEach(d => {
      if (d !== panel) d.classList.remove('open');
    });

    if (!isOpen) {
      panel.classList.add('open');
      await loadNotifications();
    } else {
      panel.classList.remove('open');
    }
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('.fk-notif-btn') && !e.target.closest('.fk-notif-panel')) {
      panel.classList.remove('open');
    }
  });

  // Mark all read button
  const markAllBtn = panel.querySelector('[data-action="mark-all-read"]');
  if (markAllBtn) {
    markAllBtn.addEventListener('click', async () => {
      try {
        await FK.post('/admin/api/notifications.php', { action: 'mark_all_read' });
        const dot = document.querySelector('.fk-notif-dot');
        if (dot) dot.remove();
        panel.querySelectorAll('.fk-notif-item.unread').forEach(i => i.classList.remove('unread'));
        panel.querySelectorAll('.fk-notif-unread-dot').forEach(d => d.remove());
      } catch {
        // Silently fail
      }
    });
  }
}

async function loadNotifications() {
  const list = document.querySelector('.fk-notif-list');
  if (!list) return;

  try {
    const data = await FK.get('/admin/api/notifications.php');
    const notifs = data.notifications || [];

    if (!notifs.length) {
      list.innerHTML = `
        <div style="padding:24px;text-align:center;color:var(--fk-text-muted);font-size:13px">
          Aucune notification
        </div>
      `;
      return;
    }

    list.innerHTML = notifs.map(n => `
      <div class="fk-notif-item ${n.read ? '' : 'unread'}" data-id="${n.id}" data-url="${FK._escapeHtml(n.url||'#')}">
        <div class="fk-notif-icon" style="background:${n.bg || 'var(--fk-subtle-green)'}">
          ${n.icon || '🔔'}
        </div>
        <div class="fk-notif-body">
          <div class="fk-notif-title">${FK._escapeHtml(n.title)}</div>
          <div class="fk-notif-text">${FK._escapeHtml(n.text || '')}</div>
          <div class="fk-notif-time">${FK.timeAgo(n.created_at)}</div>
        </div>
        ${!n.read ? '<div class="fk-notif-unread-dot"></div>' : ''}
      </div>
    `).join('');

    list.querySelectorAll('.fk-notif-item').forEach(item => {
      item.addEventListener('click', async () => {
        const id  = item.dataset.id;
        const url = item.dataset.url;
        if (id) {
          await FK.post('/admin/api/notifications.php', { action: 'mark_read', id });
          item.classList.remove('unread');
          item.querySelector('.fk-notif-unread-dot')?.remove();
        }
        if (url && url !== '#') window.location.href = url;
      });
    });
  } catch {
    list.innerHTML = `<div style="padding:16px;text-align:center;color:var(--fk-text-muted);font-size:13px">Erreur de chargement</div>`;
  }
}

/* =========================================================
   AVATAR DROPDOWN
   ========================================================= */
function initAvatarDropdown() {
  const trigger  = document.querySelector('[data-dropdown="avatar"]');
  const dropdown = document.querySelector('#avatar-dropdown');

  if (!trigger || !dropdown) return;

  trigger.addEventListener('click', e => {
    e.stopPropagation();
    const isOpen = dropdown.classList.contains('open');
    closeAllDropdowns();
    if (!isOpen) dropdown.classList.add('open');
  });

  document.addEventListener('click', closeAllDropdowns);
}

function closeAllDropdowns() {
  document.querySelectorAll('.fk-dropdown-menu.open, .fk-notif-panel.open').forEach(d => {
    d.classList.remove('open');
  });
}

/* =========================================================
   TAB NAVIGATION
   ========================================================= */
function initTabs() {
  document.querySelectorAll('.fk-tab-nav').forEach(nav => {
    const tabs     = nav.querySelectorAll('.fk-tab[data-tab]');
    const panes    = document.querySelectorAll('.fk-tab-pane');

    tabs.forEach(tab => {
      tab.addEventListener('click', e => {
        e.preventDefault();
        const target = tab.dataset.tab;

        // Update tabs
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Update panes
        panes.forEach(pane => {
          if (pane.id === target || pane.dataset.tab === target) {
            pane.classList.add('active');
          } else {
            pane.classList.remove('active');
          }
        });

        // Update URL hash (optional)
        if (tab.dataset.tabUrl !== 'false') {
          history.replaceState(null, '', `#${target}`);
        }
      });
    });

    // Restore from hash
    const hash = window.location.hash.replace('#', '');
    if (hash) {
      const matchTab = Array.from(tabs).find(t => t.dataset.tab === hash);
      if (matchTab) matchTab.click();
    }
  });
}

/* =========================================================
   MODAL MANAGEMENT
   ========================================================= */
function initModals() {
  // Open triggers
  document.addEventListener('click', e => {
    const trigger = e.target.closest('[data-modal-open]');
    if (trigger) {
      e.preventDefault();
      FK.modal.open(trigger.dataset.modalOpen);
    }

    const closeTrigger = e.target.closest('[data-modal-close], .fk-modal-close');
    if (closeTrigger) {
      e.preventDefault();
      const overlayId = closeTrigger.dataset.modalClose;
      if (overlayId) {
        FK.modal.close(overlayId);
      } else {
        const overlay = closeTrigger.closest('.fk-modal-overlay');
        if (overlay) FK.modal.close(overlay);
      }
    }
  });

  // Close on overlay click
  document.addEventListener('click', e => {
    if (e.target.classList.contains('fk-modal-overlay')) {
      FK.modal.close(e.target);
    }
  });

  // ESC key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') FK.modal.closeAll();
  });
}

/* =========================================================
   FORM VALIDATION (real-time)
   ========================================================= */
function initFormValidation() {
  document.querySelectorAll('.fk-input[required], .fk-select[required], .fk-textarea[required]').forEach(field => {
    field.addEventListener('blur', () => {
      FK._clearFieldError(field);
      if (!field.value.trim()) {
        FK._showFieldError(field, 'Ce champ est obligatoire');
      }
    });

    field.addEventListener('input', () => {
      if (field.classList.contains('error') && field.value.trim()) {
        FK._clearFieldError(field);
      }
    });
  });
}

/* =========================================================
   FILE UPLOAD (drag and drop)
   ========================================================= */
function initFileUploads() {
  document.querySelectorAll('.fk-upload-area').forEach(area => {
    const fileInput = area.querySelector('.fk-upload-input') || area.querySelector('input[type="file"]');
    const preview   = area.closest('.fk-form-group')?.querySelector('.fk-upload-preview')
                   || area.parentElement?.querySelector('.fk-upload-preview');

    ['dragenter', 'dragover'].forEach(evt => {
      area.addEventListener(evt, e => {
        e.preventDefault();
        area.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(evt => {
      area.addEventListener(evt, e => {
        e.preventDefault();
        area.classList.remove('dragover');
      });
    });

    area.addEventListener('drop', e => {
      const files = e.dataTransfer.files;
      if (fileInput && files.length) {
        const dt = new DataTransfer();
        Array.from(files).forEach(f => dt.items.add(f));
        fileInput.files = dt.files;
        handleFilePreview(files, preview);
      }
    });

    if (fileInput) {
      fileInput.addEventListener('change', () => {
        handleFilePreview(fileInput.files, preview);
      });
    }
  });
}

function handleFilePreview(files, previewEl) {
  if (!previewEl) return;
  previewEl.innerHTML = '';

  Array.from(files).forEach(file => {
    if (!file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = e => {
      const item = document.createElement('div');
      item.className = 'fk-upload-preview-item';
      item.innerHTML = `
        <img src="${e.target.result}" alt="${FK._escapeHtml(file.name)}">
        <button type="button" class="fk-upload-preview-remove" title="Supprimer">✕</button>
      `;
      item.querySelector('.fk-upload-preview-remove').addEventListener('click', () => {
        item.remove();
      });
      previewEl.appendChild(item);
    };
    reader.readAsDataURL(file);
  });
}

/* =========================================================
   DYNAMIC CHILDREN FIELDS
   ========================================================= */
function initChildrenFields() {
  const countSelects = document.querySelectorAll('[data-children-count]');

  countSelects.forEach(select => {
    const targetId = select.dataset.childrenCount;
    const container = document.getElementById(targetId);
    if (!container) return;

    function renderChildForms(count) {
      container.innerHTML = '';
      for (let i = 1; i <= count; i++) {
        const card = document.createElement('div');
        card.className = 'fk-child-card-admin fk-mb-3';
        card.innerHTML = `
          <div class="fk-child-card-admin-header">
            <span class="fk-child-card-admin-title">Enfant ${i}</span>
            <span class="fk-badge fk-badge-info">Enfant #${i}</span>
          </div>
          <div class="fk-form-row">
            <div class="fk-form-group">
              <label class="fk-label">Prénom</label>
              <input type="text" name="children[${i}][name]" class="fk-input" placeholder="Prénom de l'enfant">
            </div>
            <div class="fk-form-group">
              <label class="fk-label">Âge</label>
              <select name="children[${i}][age]" class="fk-select">
                <option value="">— Âge —</option>
                ${Array.from({length: 12}, (_, k) => `<option value="${k+1}">${k+1} an${k ? 's' : ''}</option>`).join('')}
              </select>
            </div>
          </div>
          <div class="fk-form-group fk-mt-3">
            <label class="fk-label">Besoins spéciaux / Allergies</label>
            <input type="text" name="children[${i}][notes]" class="fk-input" placeholder="Ex: allergie aux arachides, asthme...">
          </div>
        `;
        container.appendChild(card);
      }
    }

    select.addEventListener('change', () => {
      const count = parseInt(select.value) || 0;
      renderChildForms(count);
    });

    // Init on load
    const initCount = parseInt(select.value) || 0;
    if (initCount > 0) renderChildForms(initCount);
  });
}

/* =========================================================
   DATE/TIME VALIDATION (24h in advance)
   ========================================================= */
function initDateValidation() {
  document.querySelectorAll('[data-date-min-24h]').forEach(input => {
    const now = new Date();
    now.setHours(now.getHours() + 24);
    const minDate = now.toISOString().slice(0, 16);
    input.min = minDate;

    input.addEventListener('change', () => {
      const selected = new Date(input.value);
      const minTime  = new Date();
      minTime.setHours(minTime.getHours() + 24);

      if (selected < minTime) {
        FK._showFieldError(input, 'La réservation doit être effectuée au moins 24h à l\'avance');
        input.value = '';
      } else {
        FK._clearFieldError(input);
      }
    });
  });
}

/* =========================================================
   COPY BUTTONS
   ========================================================= */
function initCopyButtons() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    e.preventDefault();
    const text = btn.dataset.copy ||
                 document.getElementById(btn.dataset.copyFrom)?.textContent ||
                 btn.closest('.fk-copy-row')?.querySelector('.fk-copy-row-text')?.textContent;
    if (text) FK.copyToClipboard(text.trim());
  });
}

/* =========================================================
   PRINT HANDLER
   ========================================================= */
function initPrintButton() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-print]');
    if (!btn) return;
    e.preventDefault();
    window.print();
  });
}

/* =========================================================
   WHATSAPP MESSAGE BUILDER
   ========================================================= */
const WABuilder = {
  init() {
    const builder = document.getElementById('fk-wa-builder');
    if (!builder) return;

    const templateSelect = builder.querySelector('[data-wa-template]');
    const preview        = builder.querySelector('[data-wa-preview]');
    const sendBtn        = builder.querySelector('[data-wa-send]');
    const phoneInput     = builder.querySelector('[data-wa-phone]');

    if (templateSelect) {
      templateSelect.addEventListener('change', () => WABuilder.updatePreview(builder));
    }

    builder.addEventListener('input', FK.debounce(() => WABuilder.updatePreview(builder), 300));

    if (sendBtn) {
      sendBtn.addEventListener('click', () => {
        const phone = (phoneInput?.value || '').replace(/\D/g, '');
        const text  = preview?.textContent || '';
        if (!phone) { FK.toast('Entrez un numéro de téléphone', 'warning'); return; }
        if (!text)  { FK.toast('Message vide', 'warning'); return; }
        const url = `https://wa.me/${phone}?text=${encodeURIComponent(text)}`;
        window.open(url, '_blank');
      });
    }
  },

  updatePreview(builder) {
    const preview  = builder.querySelector('[data-wa-preview]');
    const template = builder.querySelector('[data-wa-template]');
    if (!preview || !template) return;

    let message = template.options[template.selectedIndex]?.dataset.template || '';

    // Replace variables from inputs
    builder.querySelectorAll('[data-wa-var]').forEach(input => {
      const key = input.dataset.waVar;
      message = message.replace(new RegExp(`\\{${key}\\}`, 'g'), input.value || `{${key}}`);
    });

    preview.textContent = message;
  },
};

/* =========================================================
   AUTO-REFRESH DASHBOARD KPIs
   ========================================================= */
let dashboardRefreshTimer = null;

function initDashboardAutoRefresh() {
  const kpiContainer = document.getElementById('fk-kpi-container');
  if (!kpiContainer) return;

  async function refreshKPIs() {
    try {
      const data = await FK.get('/admin/api/dashboard-kpis.php');
      if (data.kpis) {
        Object.entries(data.kpis).forEach(([key, val]) => {
          const el = document.querySelector(`[data-kpi="${key}"]`);
          if (el) el.textContent = val;
        });
      }
    } catch {
      // Silently fail auto-refresh
    }
  }

  dashboardRefreshTimer = setInterval(refreshKPIs, 60000);

  // Cleanup on page unload
  window.addEventListener('beforeunload', () => {
    if (dashboardRefreshTimer) clearInterval(dashboardRefreshTimer);
  });
}

/* =========================================================
   CHART.JS INTEGRATION
   ========================================================= */
function initCharts() {
  if (typeof Chart === 'undefined') {
    // Load Chart.js from CDN dynamically
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
    script.onload = () => renderCharts();
    script.onerror = () => console.warn('[FK] Chart.js failed to load');
    document.head.appendChild(script);
  } else {
    renderCharts();
  }
}

function renderCharts() {
  // Revenue bar chart
  const revenueCanvas = document.getElementById('fk-revenue-chart');
  if (revenueCanvas) {
    let chartData;
    try {
      chartData = JSON.parse(revenueCanvas.dataset.chart || '{}');
    } catch { chartData = {}; }

    const labels = chartData.labels || ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const values = chartData.values || [0, 0, 0, 0, 0, 0, 0];

    new Chart(revenueCanvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Revenu (MAD)',
          data: values,
          backgroundColor: 'rgba(45,106,79,0.75)',
          borderColor: '#2D6A4F',
          borderWidth: 2,
          borderRadius: 6,
          borderSkipped: false,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => FK.formatPrice(ctx.parsed.y),
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 11, family: 'Inter' }, color: '#6B7A72' },
          },
          y: {
            grid: { color: '#F0F5F2' },
            ticks: {
              font: { size: 11, family: 'Inter' },
              color: '#6B7A72',
              callback: v => FK.formatPrice(v),
            },
            beginAtZero: true,
          },
        },
      },
    });
  }

  // Channel donut chart
  const channelCanvas = document.getElementById('fk-channel-chart');
  if (channelCanvas) {
    let channelData;
    try {
      channelData = JSON.parse(channelCanvas.dataset.chart || '{}');
    } catch { channelData = {}; }

    const labels = channelData.labels || ['Hôtel', 'Ville', 'Direct'];
    const values = channelData.values || [60, 25, 15];

    new Chart(channelCanvas, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: values,
          backgroundColor: ['#2D6A4F', '#52B788', '#E8C342'],
          borderWidth: 0,
          hoverOffset: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: { size: 12, family: 'Inter' },
              color: '#6B7A72',
              padding: 16,
              usePointStyle: true,
              pointStyleWidth: 8,
            },
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${ctx.parsed}%`,
            },
          },
        },
      },
    });
  }
}

/* =========================================================
   CALENDAR (WEEK VIEW)
   ========================================================= */
const FKCalendar = {
  currentDate: new Date(),
  bookings: [],

  init() {
    const container = document.getElementById('fk-calendar');
    if (!container) return;

    this.renderWeekView();
    this.initNavigation();
    this.loadBookings();
  },

  getWeekDates(anchor = this.currentDate) {
    const dates = [];
    const start = new Date(anchor);
    const day   = start.getDay();
    const diff  = day === 0 ? -6 : 1 - day;
    start.setDate(start.getDate() + diff);

    for (let i = 0; i < 7; i++) {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      dates.push(d);
    }
    return dates;
  },

  formatWeekLabel(dates) {
    const fr = d => d.toLocaleDateString('fr-MA', { day: '2-digit', month: 'short' });
    return `${fr(dates[0])} – ${fr(dates[6])}`;
  },

  renderWeekView() {
    const container = document.getElementById('fk-calendar');
    if (!container) return;

    const dates     = this.getWeekDates();
    const today     = new Date();
    const dayNames  = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const hours     = [];

    for (let h = 8; h <= 22; h++) {
      hours.push(`${String(h).padStart(2,'0')}:00`);
    }

    const navTitle = document.querySelector('.fk-calendar-nav-title');
    if (navTitle) navTitle.textContent = this.formatWeekLabel(dates);

    let html = `<div class="fk-week-grid" id="fk-week-grid">`;

    // Header row
    html += `<div class="fk-week-header-cell" style="background:var(--fk-light-bg);border-bottom:1px solid var(--fk-border)"></div>`;
    dates.forEach((d, i) => {
      const isToday = d.toDateString() === today.toDateString();
      html += `
        <div class="fk-week-header-cell ${isToday ? 'today' : ''}" data-date="${d.toISOString().slice(0,10)}">
          <div>${dayNames[i]}</div>
          <div style="font-size:16px;font-weight:700;margin-top:2px">${d.getDate()}</div>
        </div>
      `;
    });

    // Time rows
    hours.forEach(hour => {
      html += `<div class="fk-time-label">${hour}</div>`;
      dates.forEach(d => {
        html += `<div class="fk-cal-cell" data-date="${d.toISOString().slice(0,10)}" data-hour="${hour}"></div>`;
      });
    });

    html += '</div>';
    container.innerHTML = html;
  },

  async loadBookings() {
    const dates = this.getWeekDates();
    const from  = dates[0].toISOString().slice(0,10);
    const to    = dates[6].toISOString().slice(0,10);

    try {
      const data = await FK.get(`/admin/api/calendar-bookings.php?from=${from}&to=${to}`);
      this.bookings = data.bookings || [];
      this.renderBookings();
    } catch {
      // Silently fail
    }
  },

  renderBookings() {
    // Clear old blocks
    document.querySelectorAll('.fk-booking-block').forEach(b => b.remove());

    this.bookings.forEach(booking => {
      const dateStr = booking.date;
      const startH  = parseInt(booking.start_hour) || 8;
      const endH    = parseInt(booking.end_hour) || startH + 2;
      const cell    = document.querySelector(`.fk-cal-cell[data-date="${dateStr}"][data-hour="${String(startH).padStart(2,'0')}:00"]`);
      if (!cell) return;

      const statusClass = {
        new:         'fk-booking-block-new',
        pending:     'fk-booking-block-pending',
        confirmed:   '',
        completed:   'fk-booking-block-completed',
        cancelled:   'fk-booking-block-cancelled',
      }[booking.status] || '';

      const block = document.createElement('div');
      block.className = `fk-booking-block ${statusClass}`;
      const durationCells = (endH - startH);
      block.style.height  = `${durationCells * 48 - 4}px`;
      block.style.zIndex  = '5';
      block.dataset.id    = booking.id;
      block.innerHTML     = `
        <div class="fk-truncate">${FK._escapeHtml(booking.client_name || 'Client')}</div>
        <div style="opacity:0.8;font-size:9px">${booking.start_time || ''} · ${booking.children_count || 1} enf.</div>
      `;

      block.addEventListener('click', () => FKBookingModal.open(booking.id));

      cell.appendChild(block);
    });
  },

  initNavigation() {
    const prevBtn = document.querySelector('[data-cal-prev]');
    const nextBtn = document.querySelector('[data-cal-next]');
    const todayBtn = document.querySelector('[data-cal-today]');

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        this.currentDate.setDate(this.currentDate.getDate() - 7);
        this.renderWeekView();
        this.loadBookings();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        this.currentDate.setDate(this.currentDate.getDate() + 7);
        this.renderWeekView();
        this.loadBookings();
      });
    }

    if (todayBtn) {
      todayBtn.addEventListener('click', () => {
        this.currentDate = new Date();
        this.renderWeekView();
        this.loadBookings();
      });
    }
  },
};

/* =========================================================
   BOOKING DETAIL MODAL
   ========================================================= */
const FKBookingModal = {
  async open(bookingId) {
    FK.modal.open('fk-booking-modal');
    const body = document.getElementById('fk-booking-modal-body');
    if (!body) return;

    FK.showSkeleton(body);

    try {
      const data = await FK.get(`/admin/api/booking-detail.php?id=${bookingId}`);
      FK.hideSkeleton(body);
      this.render(data.booking);
    } catch {
      FK.hideSkeleton(body);
      body.innerHTML = `<div class="fk-alert fk-alert-error">Impossible de charger la réservation</div>`;
    }
  },

  render(booking) {
    const body = document.getElementById('fk-booking-modal-body');
    if (!body || !booking) return;

    const statusBadge = `<span class="fk-badge fk-badge-${booking.status}">${this.statusLabel(booking.status)}</span>`;
    const phone = (booking.phone || '').replace(/\D/g, '');
    const waUrl = phone ? `https://wa.me/${phone}` : '#';

    body.innerHTML = `
      <div class="fk-flex fk-items-center fk-justify-between fk-mb-4">
        <div>
          <div class="fk-text-xl fk-fw-bold">${FK._escapeHtml(booking.client_name || '—')}</div>
          <div class="fk-text-sm fk-text-muted fk-mt-1">${FK._escapeHtml(booking.phone || '')} · ${FK._escapeHtml(booking.email || '')}</div>
        </div>
        <div class="fk-flex fk-items-center fk-gap-2">
          ${statusBadge}
          ${phone ? `<a href="${waUrl}" target="_blank" class="fk-whatsapp-btn fk-btn-sm">
            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            WhatsApp
          </a>` : ''}
        </div>
      </div>

      <div class="fk-grid fk-grid-2 fk-mb-4" style="gap:12px">
        <div class="fk-card fk-card-sm" style="background:var(--fk-light-bg);border:none">
          <div class="fk-text-xs fk-text-muted fk-uppercase" style="letter-spacing:.05em;margin-bottom:4px">Date & Heure</div>
          <div class="fk-fw-semibold">${FK.formatDate(booking.booking_date)}</div>
          <div class="fk-text-sm fk-text-muted">${booking.start_time || ''} – ${booking.end_time || ''}</div>
        </div>
        <div class="fk-card fk-card-sm" style="background:var(--fk-light-bg);border:none">
          <div class="fk-text-xs fk-text-muted fk-uppercase" style="letter-spacing:.05em;margin-bottom:4px">Tarif</div>
          <div class="fk-fw-bold fk-text-xl">${FK.formatPrice(booking.total_price)}</div>
          <div class="fk-text-sm fk-text-muted">${booking.duration_hours || ''}h · ${booking.children_count || 1} enfant(s)</div>
        </div>
      </div>

      <div class="fk-info-list fk-mb-4">
        <div class="fk-info-row">
          <span class="fk-info-key">Hôtel</span>
          <span class="fk-info-val">${FK._escapeHtml(booking.hotel_name || '—')}</span>
        </div>
        <div class="fk-info-row">
          <span class="fk-info-key">Chambre</span>
          <span class="fk-info-val">${FK._escapeHtml(booking.room_number || '—')}</span>
        </div>
        <div class="fk-info-row">
          <span class="fk-info-key">Babysitter</span>
          <span class="fk-info-val">${FK._escapeHtml(booking.babysitter_name || 'Non assigné')}</span>
        </div>
        <div class="fk-info-row">
          <span class="fk-info-key">Canal</span>
          <span class="fk-info-val"><span class="fk-badge fk-badge-${booking.channel === 'hotel' ? 'hotel' : 'city'}">${booking.channel || '—'}</span></span>
        </div>
      </div>

      ${(booking.children || []).length ? `
        <div class="fk-section-title">Enfants</div>
        ${(booking.children || []).map((c, i) => `
          <div class="fk-child-card-admin fk-mb-2">
            <div class="fk-flex fk-items-center fk-gap-2 fk-mb-1">
              <span class="fk-fw-semibold">${FK._escapeHtml(c.name || `Enfant ${i+1}`)}</span>
              <span class="fk-badge fk-badge-muted">${c.age || '?'} ans</span>
            </div>
            ${c.notes ? `<div class="fk-text-sm fk-text-muted">${FK._escapeHtml(c.notes)}</div>` : ''}
          </div>
        `).join('')}
      ` : ''}

      ${booking.notes ? `
        <div class="fk-section-title fk-mt-4">Notes</div>
        <div class="fk-text-sm fk-text-muted" style="line-height:1.6">${FK._escapeHtml(booking.notes)}</div>
      ` : ''}
    `;

    // Status change actions in footer
    const footer = document.querySelector('#fk-booking-modal .fk-modal-footer');
    if (footer) {
      footer.innerHTML = `
        <a href="/admin/bookings/edit.php?id=${booking.id}" class="fk-btn fk-btn-secondary fk-btn-sm">Modifier</a>
        <button class="fk-btn fk-btn-primary fk-btn-sm" data-action="change-status" data-id="${booking.id}">
          Changer statut
        </button>
      `;
    }
  },

  statusLabel(status) {
    const labels = {
      new:         'Nouveau',
      pending:     'En attente',
      confirmed:   'Confirmé',
      in_progress: 'En cours',
      completed:   'Terminé',
      cancelled:   'Annulé',
      no_show:     'No-show',
    };
    return labels[status] || status;
  },
};

/* =========================================================
   HOTEL PAGE TABS
   ========================================================= */
function initHotelTabs() {
  const tabNav = document.querySelector('#hotel-tabs .fk-tab-nav');
  if (!tabNav) return;
  // Handled by initTabs() already
}

/* =========================================================
   SETTINGS — SMTP TEST
   ========================================================= */
function initSmtpTest() {
  const testBtn = document.getElementById('fk-smtp-test-btn');
  if (!testBtn) return;

  testBtn.addEventListener('click', async () => {
    FK.setLoading(testBtn, true);
    try {
      const form = document.getElementById('fk-smtp-form');
      const data = FK.serializeForm(form);
      const res  = await FK.post('/admin/api/test-smtp.php', data);
      if (res.success) {
        FK.toast('Email de test envoyé avec succès !', 'success');
      } else {
        FK.toast(res.message || 'Échec de l\'envoi', 'error');
      }
    } catch {
      FK.toast('Erreur de connexion SMTP', 'error');
    } finally {
      FK.setLoading(testBtn, false);
    }
  });
}

/* =========================================================
   GENERIC FORM SUBMIT (AJAX)
   ========================================================= */
function initAjaxForms() {
  document.querySelectorAll('form[data-ajax]').forEach(form => {
    form.addEventListener('submit', async e => {
      e.preventDefault();

      if (!FK.validateForm(form)) {
        FK.toast('Veuillez corriger les erreurs', 'warning');
        return;
      }

      const submitBtn = form.querySelector('[type="submit"]');
      FK.setLoading(submitBtn, true);

      try {
        const action = form.action || window.location.href;
        let res;

        if (form.enctype === 'multipart/form-data') {
          res = await FK.postForm(action, new FormData(form));
        } else {
          res = await FK.post(action, FK.serializeForm(form));
        }

        if (res.success) {
          FK.toast(res.message || 'Enregistré avec succès', 'success');
          if (res.redirect) {
            setTimeout(() => window.location.href = res.redirect, 800);
          }
          if (form.dataset.resetOnSuccess !== undefined) {
            FK.resetForm(form);
          }
        } else {
          FK.toast(res.message || 'Une erreur est survenue', 'error');
          if (res.errors) {
            Object.entries(res.errors).forEach(([field, msg]) => {
              const input = form.querySelector(`[name="${field}"]`);
              if (input) FK._showFieldError(input, msg);
            });
          }
        }
      } catch {
        FK.toast('Erreur de connexion', 'error');
      } finally {
        FK.setLoading(submitBtn, false);
      }
    });
  });
}

/* =========================================================
   DELETE ACTIONS
   ========================================================= */
function initDeleteActions() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-delete]');
    if (!btn) return;
    e.preventDefault();

    const url   = btn.dataset.delete;
    const label = btn.dataset.deleteLabel || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
    const name  = btn.dataset.deleteName || '';

    FK.modal.confirm(
      label,
      async () => {
        FK.setLoading(btn, true);
        try {
          const res = await FK.post(url, { action: 'delete', name });
          if (res.success) {
            FK.toast(res.message || 'Supprimé avec succès', 'success');
            const row = btn.closest('tr');
            if (row) row.remove();
            else if (res.redirect) window.location.href = res.redirect;
            else window.location.reload();
          } else {
            FK.toast(res.message || 'Impossible de supprimer', 'error');
          }
        } catch {
          FK.toast('Erreur lors de la suppression', 'error');
        } finally {
          FK.setLoading(btn, false);
        }
      },
      {
        title:  'Confirmer la suppression',
        label:  'Supprimer',
        type:   'danger',
        detail: name ? `Élément : ${name}` : '',
      }
    );
  });
}

/* =========================================================
   STATUS CHANGE ACTIONS
   ========================================================= */
function initStatusActions() {
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-status-change]');
    if (!btn) return;

    const id      = btn.dataset.statusChange;
    const status  = btn.dataset.status;
    const label   = btn.dataset.statusLabel || status;

    FK.modal.confirm(
      `Changer le statut vers "${label}" ?`,
      async () => {
        try {
          const res = await FK.post('/admin/api/booking-status.php', { id, status });
          if (res.success) {
            FK.toast(`Statut changé: ${label}`, 'success');
            setTimeout(() => window.location.reload(), 600);
          } else {
            FK.toast(res.message || 'Échec du changement', 'error');
          }
        } catch {
          FK.toast('Erreur', 'error');
        }
      },
      { title: 'Changer le statut', label: 'Confirmer', type: 'warning' }
    );
  });
}

/* =========================================================
   FILTER BAR PILLS
   ========================================================= */
function initFilterPills() {
  document.querySelectorAll('.fk-filter-pill[data-filter]').forEach(pill => {
    pill.addEventListener('click', () => {
      const group = pill.dataset.filterGroup;
      if (group) {
        document.querySelectorAll(`.fk-filter-pill[data-filter-group="${group}"]`).forEach(p => {
          p.classList.remove('active');
        });
      }
      pill.classList.toggle('active');

      // Trigger filter update
      const event = new CustomEvent('fk:filter-change', {
        detail: { filter: pill.dataset.filter, active: pill.classList.contains('active') },
        bubbles: true,
      });
      pill.dispatchEvent(event);
    });
  });
}

/* =========================================================
   KEYBOARD SHORTCUTS
   ========================================================= */
function initKeyboardShortcuts() {
  document.addEventListener('keydown', e => {
    // Ctrl/Cmd + K — focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      const searchInput = document.querySelector('.fk-search-input');
      if (searchInput) searchInput.focus();
    }

    // Ctrl/Cmd + N — new booking
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
      e.preventDefault();
      const newBtn = document.querySelector('[data-shortcut="new-booking"]');
      if (newBtn) newBtn.click();
    }
  });
}

/* =========================================================
   INIT — DOM READY
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initActiveNav();
  initSearch();
  initNotifications();
  initAvatarDropdown();
  initTabs();
  initModals();
  initFormValidation();
  initFileUploads();
  initChildrenFields();
  initDateValidation();
  initCopyButtons();
  initPrintButton();
  initDeleteActions();
  initStatusActions();
  initFilterPills();
  initAjaxForms();
  initSmtpTest();
  initKeyboardShortcuts();
  WABuilder.init();

  // Page-specific init
  if (document.getElementById('fk-kpi-container')) {
    initDashboardAutoRefresh();
  }

  if (document.getElementById('fk-revenue-chart') || document.getElementById('fk-channel-chart')) {
    initCharts();
  }

  if (document.getElementById('fk-calendar')) {
    FKCalendar.init();
  }
});

/* =========================================================
   EXPOSE TO GLOBAL SCOPE
   ========================================================= */
window.FK         = FK;
window.FKCalendar = FKCalendar;
window.FKBookingModal = FKBookingModal;
window.WABuilder  = WABuilder;
