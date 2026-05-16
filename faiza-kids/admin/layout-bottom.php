        </div><!-- /.fk-content -->
    </div><!-- /.fk-main -->
</div><!-- /.fk-shell -->

<!-- ═══════════════════ NOTIFICATIONS PANEL ═══════════════════ -->
<div class="fk-notif-panel" id="fkNotifPanel" role="dialog" aria-label="Notifications" aria-hidden="true">
    <div class="fk-notif-panel-inner">
        <div class="fk-notif-panel-header">
            <strong>Notifications</strong>
            <button class="fk-notif-mark-all" onclick="fkMarkAllRead()" title="Tout marquer comme lu">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tout lire
            </button>
        </div>
        <div class="fk-notif-list" id="fkNotifList">
            <!-- Injected via JS or PHP include -->
            <p class="fk-notif-empty">Aucune notification.</p>
        </div>
        <div class="fk-notif-panel-footer">
            <a href="/admin/notifications">Voir toutes les notifications</a>
        </div>
    </div>
</div>
<div class="fk-notif-backdrop" id="fkNotifBackdrop" onclick="fkCloseNotifications()"></div>

<!-- ═══════════════════ GLOBAL CONFIRM MODAL ═══════════════════ -->
<div class="fk-modal-wrap" id="fkConfirmModal" role="dialog" aria-modal="true" aria-labelledby="fkConfirmTitle" aria-hidden="true">
    <div class="fk-modal">
        <div class="fk-modal-icon" id="fkConfirmIcon">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2" aria-hidden="true">
                <path d="M12 9v4M12 17h.01" stroke-linecap="round"/>
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h3 class="fk-modal-title" id="fkConfirmTitle">Confirmer l'action</h3>
        <p class="fk-modal-body" id="fkConfirmBody">Êtes-vous sûr de vouloir effectuer cette action ? Cette opération est irréversible.</p>
        <div class="fk-modal-actions">
            <button class="fk-btn fk-btn-secondary" onclick="fkCloseModal('fkConfirmModal')">Annuler</button>
            <button class="fk-btn fk-btn-danger" id="fkConfirmOkBtn">Confirmer</button>
        </div>
    </div>
</div>

<!-- ═══════════════════ WHATSAPP MODAL ═══════════════════ -->
<div class="fk-modal-wrap" id="fkWaModal" role="dialog" aria-modal="true" aria-labelledby="fkWaModalTitle" aria-hidden="true">
    <div class="fk-modal">
        <div class="fk-modal-icon" style="background:rgba(37,211,102,.1)">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="#25D166" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M11.998 2.003C6.484 2.003 2.003 6.484 2.003 12c0 1.76.458 3.413 1.258 4.851L2 22l5.293-1.238A9.942 9.942 0 0012 21.998c5.516 0 9.997-4.481 9.997-9.997C21.997 6.484 17.514 2.003 11.998 2.003zm0 18.207a8.206 8.206 0 01-4.187-1.149l-.3-.178-3.143.734.772-3.064-.195-.316A8.156 8.156 0 013.79 12c0-4.52 3.688-8.204 8.208-8.204 4.518 0 8.205 3.685 8.205 8.205-.001 4.519-3.688 8.209-8.205 8.209z"/>
            </svg>
        </div>
        <h3 class="fk-modal-title" id="fkWaModalTitle">Envoyer via WhatsApp</h3>
        <div class="fk-modal-field">
            <label class="fk-modal-label" for="fkWaPhone">Numéro de téléphone</label>
            <input class="fk-input" id="fkWaPhone" type="tel" placeholder="+212 6XX XXX XXX" readonly>
        </div>
        <div class="fk-modal-field">
            <label class="fk-modal-label" for="fkWaMessage">Message</label>
            <textarea class="fk-input fk-textarea" id="fkWaMessage" rows="4" placeholder="Tapez votre message…"></textarea>
        </div>
        <div class="fk-modal-actions">
            <button class="fk-btn fk-btn-secondary" onclick="fkCloseModal('fkWaModal')">Annuler</button>
            <a href="#" id="fkWaSendBtn" class="fk-btn fk-btn-wa" target="_blank" rel="noopener noreferrer" onclick="fkBuildWaLink()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
                </svg>
                Ouvrir WhatsApp
            </a>
        </div>
    </div>
</div>

<!-- ═══════════════════ TOAST CONTAINER ═══════════════════ -->
<div class="fk-toast-container" id="fkToastContainer" aria-live="polite" aria-atomic="false"></div>

<!-- ═══════════════════ SCRIPTS ═══════════════════ -->
<script src="/assets/js/app.js" defer></script>

<?= $extra_js ?? '' ?>

<style>
    /* ─── Notifications Panel ─────────────────────────── */
    .fk-notif-panel {
        position: fixed;
        top: 0; right: -380px;
        width: 360px;
        height: 100vh;
        background: #fff;
        border-left: 1px solid var(--fk-border, #E5EDE9);
        box-shadow: -4px 0 24px rgba(0,0,0,.10);
        z-index: 400;
        display: flex;
        flex-direction: column;
        transition: right .28s cubic-bezier(.4,0,.2,1);
    }
    .fk-notif-panel.fk-open { right: 0; }
    .fk-notif-panel-inner { display: flex; flex-direction: column; height: 100%; }
    .fk-notif-panel-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 20px 14px;
        border-bottom: 1px solid var(--fk-border, #E5EDE9);
        flex-shrink: 0;
    }
    .fk-notif-panel-header strong { font-size: .95rem; font-weight: 700; }
    .fk-notif-mark-all {
        background: none; border: none; cursor: pointer;
        display: flex; align-items: center; gap: 5px;
        font-size: .73rem; color: var(--fk-btn, #52B788); font-weight: 500;
    }
    .fk-notif-mark-all:hover { opacity: .75; }
    .fk-notif-list { flex: 1; overflow-y: auto; padding: 8px 0; }
    .fk-notif-empty { text-align: center; padding: 36px 20px; font-size: .82rem; color: var(--fk-muted, #6B7A72); }
    .fk-notif-item {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 20px;
        border-bottom: 1px solid var(--fk-border, #E5EDE9);
        transition: background .14s;
    }
    .fk-notif-item:hover { background: var(--fk-light-bg, #F8FAF9); }
    .fk-notif-item.fk-unread { background: rgba(82,183,136,.05); }
    .fk-notif-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--fk-btn, #52B788);
        margin-top: 5px; flex-shrink: 0;
    }
    .fk-notif-item:not(.fk-unread) .fk-notif-dot { background: var(--fk-border, #E5EDE9); }
    .fk-notif-text strong { display: block; font-size: .8rem; font-weight: 600; margin-bottom: 2px; }
    .fk-notif-text p { font-size: .75rem; color: var(--fk-muted, #6B7A72); line-height: 1.45; }
    .fk-notif-time { font-size: .68rem; color: var(--fk-muted, #6B7A72); margin-top: 4px; display: block; }
    .fk-notif-panel-footer {
        padding: 12px 20px; border-top: 1px solid var(--fk-border, #E5EDE9);
        text-align: center; flex-shrink: 0;
    }
    .fk-notif-panel-footer a { font-size: .78rem; color: var(--fk-btn, #52B788); text-decoration: none; font-weight: 500; }
    .fk-notif-backdrop {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 390;
    }
    .fk-notif-backdrop.fk-open { display: block; }

    /* ─── Modals ──────────────────────────────────────── */
    .fk-modal-wrap {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,.5);
        z-index: 500;
        align-items: center; justify-content: center;
        padding: 20px;
        backdrop-filter: blur(2px);
    }
    .fk-modal-wrap.fk-open { display: flex; }
    .fk-modal {
        background: #fff;
        border-radius: 16px;
        padding: 30px 28px;
        max-width: 440px; width: 100%;
        box-shadow: 0 20px 60px rgba(0,0,0,.2);
        animation: fkModalIn .22s ease;
    }
    @keyframes fkModalIn {
        from { opacity: 0; transform: scale(.94) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .fk-modal-icon {
        width: 56px; height: 56px;
        background: rgba(239,68,68,.08);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 18px;
    }
    .fk-modal-title { font-size: 1.05rem; font-weight: 700; color: var(--fk-text,#1A2E24); margin-bottom: 10px; }
    .fk-modal-body { font-size: .84rem; color: var(--fk-muted,#6B7A72); line-height: 1.55; margin-bottom: 22px; }
    .fk-modal-field { margin-bottom: 16px; }
    .fk-modal-label { display: block; font-size: .78rem; font-weight: 600; color: var(--fk-text,#1A2E24); margin-bottom: 6px; }
    .fk-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }

    /* ─── Buttons ─────────────────────────────────────── */
    .fk-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 18px; border-radius: 9px;
        font-size: .82rem; font-weight: 600;
        cursor: pointer; border: none; text-decoration: none;
        transition: background .15s, box-shadow .15s, transform .1s;
        white-space: nowrap; font-family: inherit;
    }
    .fk-btn:active { transform: translateY(1px); }
    .fk-btn-primary { background: var(--fk-btn,#52B788); color: #fff; }
    .fk-btn-primary:hover { background: #3d9e70; }
    .fk-btn-secondary {
        background: var(--fk-light-bg,#F8FAF9);
        color: var(--fk-text,#1A2E24);
        border: 1px solid var(--fk-border,#E5EDE9);
    }
    .fk-btn-secondary:hover { background: #eef3f0; }
    .fk-btn-danger { background: #EF4444; color: #fff; }
    .fk-btn-danger:hover { background: #dc2626; }
    .fk-btn-wa { background: #25D166; color: #fff; }
    .fk-btn-wa:hover { background: #1da854; }
    .fk-btn-sm { padding: 6px 12px; font-size: .75rem; }
    .fk-btn-icon { padding: 7px; }

    /* ─── Form Inputs ─────────────────────────────────── */
    .fk-input {
        display: block; width: 100%;
        padding: 9px 12px;
        border: 1px solid var(--fk-border,#E5EDE9);
        border-radius: 8px;
        font-size: .82rem; font-family: inherit;
        color: var(--fk-text,#1A2E24);
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
        outline: none;
    }
    .fk-input:focus { border-color: #52B788; box-shadow: 0 0 0 3px rgba(82,183,136,.12); }
    .fk-textarea { resize: vertical; min-height: 80px; }

    /* ─── Toasts ──────────────────────────────────────── */
    .fk-toast-container {
        position: fixed; bottom: 24px; right: 24px;
        display: flex; flex-direction: column; gap: 10px;
        z-index: 600; pointer-events: none;
    }
    .fk-toast {
        display: flex; align-items: center; gap: 12px;
        padding: 13px 18px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0,0,0,.14);
        border-left: 4px solid var(--fk-btn,#52B788);
        font-size: .82rem; font-weight: 500;
        min-width: 260px; max-width: 380px;
        pointer-events: auto;
        animation: fkToastIn .24s ease;
    }
    .fk-toast.fk-toast-error { border-left-color: #EF4444; }
    .fk-toast.fk-toast-warn  { border-left-color: var(--fk-gold,#E8C342); }
    .fk-toast-icon { flex-shrink: 0; }
    .fk-toast-text { flex: 1; color: var(--fk-text,#1A2E24); }
    .fk-toast-close {
        background: none; border: none; cursor: pointer;
        color: var(--fk-muted,#6B7A72); padding: 2px; line-height: 1;
    }
    .fk-toast-close:hover { color: var(--fk-text,#1A2E24); }
    @keyframes fkToastIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fkToastOut {
        from { opacity: 1; transform: translateY(0); }
        to   { opacity: 0; transform: translateY(10px); }
    }
</style>

<script>
(function () {
    'use strict';

    // ── Sidebar (mobile) ────────────────────────────────────────────
    window.fkOpenSidebar = function () {
        document.getElementById('fkSidebar').classList.add('fk-sidebar-open');
        document.getElementById('fkOverlay').classList.add('fk-open');
        document.body.style.overflow = 'hidden';
    };
    window.fkCloseSidebar = function () {
        document.getElementById('fkSidebar').classList.remove('fk-sidebar-open');
        document.getElementById('fkOverlay').classList.remove('fk-open');
        document.body.style.overflow = '';
    };

    // ── Avatar dropdown ─────────────────────────────────────────────
    window.fkToggleAvatarDropdown = function () {
        const dd  = document.getElementById('fkAvatarDropdown');
        const btn = document.querySelector('.fk-avatar-btn');
        const isOpen = dd.classList.toggle('fk-open');
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };
    document.addEventListener('click', function (e) {
        const wrap = document.getElementById('fkAvatarWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('fkAvatarDropdown')?.classList.remove('fk-open');
            document.querySelector('.fk-avatar-btn')?.setAttribute('aria-expanded', 'false');
        }
    });

    // ── Notifications panel ─────────────────────────────────────────
    window.fkToggleNotifications = function () {
        const panel    = document.getElementById('fkNotifPanel');
        const backdrop = document.getElementById('fkNotifBackdrop');
        const isOpen   = panel.classList.toggle('fk-open');
        backdrop.classList.toggle('fk-open', isOpen);
        panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (isOpen) fkLoadNotifications();
    };
    window.fkCloseNotifications = function () {
        document.getElementById('fkNotifPanel')?.classList.remove('fk-open');
        document.getElementById('fkNotifBackdrop')?.classList.remove('fk-open');
        document.getElementById('fkNotifPanel')?.setAttribute('aria-hidden', 'true');
    };
    window.fkLoadNotifications = function () {
        const list = document.getElementById('fkNotifList');
        if (!list || list.dataset.loaded) return;
        fetch('/api/notifications.php?limit=20', { credentials: 'same-origin' })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || !data.success || !data.notifications?.length) return;
                list.innerHTML = '';
                data.notifications.forEach(n => {
                    const div = document.createElement('div');
                    div.className = 'fk-notif-item' + (n.is_read ? '' : ' fk-unread');
                    div.innerHTML = `
                        <div class="fk-notif-dot"></div>
                        <div class="fk-notif-text">
                            <strong>${fkEsc(n.title)}</strong>
                            <p>${fkEsc(n.message)}</p>
                            <span class="fk-notif-time">${fkEsc(n.time_ago || '')}</span>
                        </div>`;
                    list.appendChild(div);
                });
                list.dataset.loaded = '1';
            })
            .catch(() => {});
    };
    window.fkMarkAllRead = function () {
        fetch('/api/notifications-read.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf_token=' + encodeURIComponent(document.querySelector('meta[name="csrf"]')?.content || '')
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                document.querySelectorAll('.fk-notif-item.fk-unread').forEach(el => el.classList.remove('fk-unread'));
                const badge = document.getElementById('fkNotifCount');
                if (badge) badge.style.display = 'none';
            }
        })
        .catch(() => {});
    };

    // ── Generic modal ───────────────────────────────────────────────
    window.fkOpenModal = function (id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.add('fk-open');
        m.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };
    window.fkCloseModal = function (id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.remove('fk-open');
        m.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };
    // Close modals on backdrop click
    document.querySelectorAll('.fk-modal-wrap').forEach(wrap => {
        wrap.addEventListener('click', function (e) {
            if (e.target === wrap) fkCloseModal(wrap.id);
        });
    });

    // ── Confirm dialog ──────────────────────────────────────────────
    let _confirmCallback = null;
    window.fkConfirm = function (msg, callback, opts) {
        opts = opts || {};
        document.getElementById('fkConfirmBody').textContent = msg;
        document.getElementById('fkConfirmTitle').textContent = opts.title || 'Confirmer l\'action';
        const btn = document.getElementById('fkConfirmOkBtn');
        btn.textContent = opts.okLabel || 'Confirmer';
        btn.className = 'fk-btn ' + (opts.btnClass || 'fk-btn-danger');
        _confirmCallback = callback;
        fkOpenModal('fkConfirmModal');
    };
    document.getElementById('fkConfirmOkBtn').addEventListener('click', function () {
        fkCloseModal('fkConfirmModal');
        if (typeof _confirmCallback === 'function') _confirmCallback();
        _confirmCallback = null;
    });

    // ── WhatsApp modal ──────────────────────────────────────────────
    window.fkOpenWa = function (phone, message) {
        document.getElementById('fkWaPhone').value   = phone   || '';
        document.getElementById('fkWaMessage').value = message || '';
        fkOpenModal('fkWaModal');
    };
    window.fkBuildWaLink = function () {
        const phone = document.getElementById('fkWaPhone').value.replace(/\D/g, '');
        const msg   = document.getElementById('fkWaMessage').value;
        const link  = document.getElementById('fkWaSendBtn');
        link.href = 'https://wa.me/' + phone + (msg ? '?text=' + encodeURIComponent(msg) : '');
        fkCloseModal('fkWaModal');
    };

    // ── Toast ───────────────────────────────────────────────────────
    window.fkToast = function (message, type) {
        type = type || 'success';
        const container = document.getElementById('fkToastContainer');
        if (!container) return;
        const icons = {
            success: '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#52B788" stroke-width="2.5"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            error:   '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#EF4444" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>',
            warn:    '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#E8C342" stroke-width="2.5"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
        };
        const toast = document.createElement('div');
        toast.className = 'fk-toast' + (type === 'error' ? ' fk-toast-error' : type === 'warn' ? ' fk-toast-warn' : '');
        toast.innerHTML = `
            <span class="fk-toast-icon">${icons[type] || icons.success}</span>
            <span class="fk-toast-text">${fkEsc(message)}</span>
            <button class="fk-toast-close" onclick="this.closest('.fk-toast').remove()" aria-label="Fermer">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
            </button>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'fkToastOut .24s ease forwards';
            setTimeout(() => toast.remove(), 260);
        }, 4500);
    };

    // ── Utility: escape HTML ────────────────────────────────────────
    function fkEsc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ── Escape key closes things ────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            fkCloseSidebar();
            fkCloseNotifications();
            document.querySelectorAll('.fk-modal-wrap.fk-open').forEach(m => fkCloseModal(m.id));
            document.getElementById('fkAvatarDropdown')?.classList.remove('fk-open');
        }
    });
})();
</script>

</body>
</html>
