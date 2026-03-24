/**
 * Nauti-Connect App Utilities
 */

// ============================================================
// Auth Modal
// ============================================================
(function () {
    'use strict';

    var SITE_URL = (function () {
        // Derive SITE_URL from the page origin + pathname prefix
        // (works for both root-installed and subdirectory installs)
        var base = document.querySelector('meta[name="site-url"]');
        return base ? base.getAttribute('content') : window.location.origin;
    }());

    var overlay  = document.getElementById('auth-modal');
    if (!overlay) return; // not rendered (user is logged in)

    var box       = document.getElementById('auth-modal-box');
    var closeBtn  = document.getElementById('auth-modal-close');
    var tabLogin  = document.getElementById('auth-tab-login');
    var tabReg    = document.getElementById('auth-tab-register');
    var panelLogin = document.getElementById('auth-panel-login');
    var panelReg   = document.getElementById('auth-panel-register');
    var errorBox   = document.getElementById('auth-modal-error');
    var loginForm  = document.getElementById('modal-login-form');
    var regForm    = document.getElementById('modal-register-form');
    var loginBtn   = document.getElementById('modal-login-btn');
    var regBtn     = document.getElementById('modal-register-btn');

    function openModal(tab) {
        showTab(tab || 'login');
        clearError();
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        // Focus first visible input
        setTimeout(function () {
            var input = (tab === 'register' ? panelReg : panelLogin).querySelector('input');
            if (input) input.focus();
        }, 120);
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
        clearError();
    }

    function showTab(tab) {
        var isReg = tab === 'register';
        tabLogin.classList.toggle('active', !isReg);
        tabReg.classList.toggle('active', isReg);
        panelLogin.classList.toggle('hidden', isReg);
        panelReg.classList.toggle('hidden', !isReg);
        box.classList.toggle('register-active', isReg);
        clearError();
    }

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.classList.remove('hidden');
    }

    function clearError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    // Open triggers – any element with data-open-modal="login|register"
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-open-modal]');
        if (trigger) {
            e.preventDefault();
            openModal(trigger.getAttribute('data-open-modal'));
        }
    });

    // Tab switching
    document.addEventListener('click', function (e) {
        var switchBtn = e.target.closest('[data-switch-tab]');
        if (switchBtn) {
            showTab(switchBtn.getAttribute('data-switch-tab'));
        }
    });

    // Tab buttons inside the modal
    [tabLogin, tabReg].forEach(function (btn) {
        btn.addEventListener('click', function () {
            showTab(btn.getAttribute('data-tab'));
        });
    });

    // Close
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    // ── AJAX submit helper ──
    function ajaxSubmit(form, btn, endpoint, onSuccess) {
        btn.classList.add('btn-loading');
        btn.disabled = true;
        clearError();

        var data = new FormData(form);

        fetch(endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data,
            credentials: 'same-origin',
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            // Refresh CSRF token in the form if returned (token is rotated after use)
            if (json.new_csrf) {
                var csrfInput = form.querySelector('[name="csrf_token"]');
                if (csrfInput) csrfInput.value = json.new_csrf;
            }
            if (json.success) {
                onSuccess(json.redirect || window.location.href);
            } else {
                var msg = json.error || (json.errors && json.errors.join(' ')) || 'Something went wrong.';
                showError(msg);
            }
        })
        .catch(function () {
            showError('Network error. Please try again.');
        })
        .finally(function () {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
        });
    }

    // ── Login form ──
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxSubmit(loginForm, loginBtn, SITE_URL + '/auth/login.php', function (redirectTo) {
            window.location.href = redirectTo;
        });
    });

    // ── Register form ──
    regForm.addEventListener('submit', function (e) {
        e.preventDefault();
        ajaxSubmit(regForm, regBtn, SITE_URL + '/auth/register.php', function (redirectTo) {
            window.location.href = redirectTo;
        });
    });
}());

// ============================================================
// Mobile Menu Toggle
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const btn        = document.getElementById('mobile-menu-btn');
    const menu       = document.getElementById('mobile-menu');
    const hamIcon    = document.getElementById('hamburger-icon');
    const closeIcon  = document.getElementById('close-icon');

    if (btn && menu) {
        btn.addEventListener('click', function () {
            const isOpen = !menu.classList.contains('hidden');
            menu.classList.toggle('hidden', isOpen);
            hamIcon?.classList.toggle('hidden', !isOpen);
            closeIcon?.classList.toggle('hidden', isOpen);
        });
    }

    // Auto-dismiss flash messages
    document.querySelectorAll('.flash-message').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });
});

// ============================================================
// Image Preview
// ============================================================
/**
 * @param {HTMLInputElement} input
 * @param {string} previewContainerId
 * @param {number} max
 */
function previewImages(input, previewContainerId, max = 10) {
    const container = document.getElementById(previewContainerId);
    if (!container) return;
    container.innerHTML = '';

    const files = Array.from(input.files).slice(0, max);
    files.forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const div = document.createElement('div');
            div.className = 'img-preview-item';
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            div.appendChild(img);
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

// ============================================================
// CSRF Token helper for fetch() requests
// ============================================================
function getCsrfMeta() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Wrapper around fetch that includes CSRF header
 */
async function apiFetch(url, options = {}) {
    const headers = Object.assign({
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfMeta(),
    }, options.headers || {});

    return fetch(url, Object.assign({}, options, { headers }));
}

// ============================================================
// Progress Slider Live Label
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="range"].progress-slider').forEach(slider => {
        const targetId = slider.getAttribute('data-label');
        if (!targetId) return;
        const label = document.getElementById(targetId);
        if (label) {
            slider.addEventListener('input', () => {
                label.textContent = slider.value + '%';
            });
        }
    });
});

// ============================================================
// AJAX Guide Search (debounced)
// ============================================================
(function () {
    const searchInput = document.getElementById('guide-search');
    const grid        = document.getElementById('guides-grid');
    if (!searchInput || !grid) return;

    let debounceTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 2) return;
        debounceTimer = setTimeout(() => {
            const form = document.getElementById('guide-search-form');
            if (form) form.submit();
        }, 600);
    });
})();

// ============================================================
// Confirm delete/destructive actions
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});

// ============================================================
// Escape HTML (shared utility — also defined in map.js but may
// be used independently here)
// ============================================================
if (typeof window.escHtml === 'undefined') {
    window.escHtml = function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };
}
