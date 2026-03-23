/**
 * Nauti-Connect App Utilities
 */

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
