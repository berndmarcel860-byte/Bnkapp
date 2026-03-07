/* ==========================================================================
   BnkApp Admin — Custom JavaScript
   Requires Bootstrap 5.3 bundle (already loaded before this file)
   ========================================================================== */

'use strict';

// ---------------------------------------------------------------------------
// Sidebar toggle
// ---------------------------------------------------------------------------
(function () {
  const sidebar  = document.getElementById('sidebar');
  const toggle   = document.getElementById('sidebarToggle');
  const COLLAPSED_KEY = 'bnkapp_sidebar_collapsed';

  if (!sidebar || !toggle) return;

  function applyState() {
    const isMobile = window.innerWidth < 769;
    if (isMobile) {
      sidebar.classList.toggle('mobile-open', !sidebar.classList.contains('mobile-open'));
    } else {
      const collapsed = sidebar.classList.toggle('collapsed');
      localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0');
    }
  }

  // Restore persisted desktop state
  if (window.innerWidth >= 769 && localStorage.getItem(COLLAPSED_KEY) === '1') {
    sidebar.classList.add('collapsed');
  }

  toggle.addEventListener('click', applyState);

  // Close sidebar on mobile when clicking outside
  document.addEventListener('click', function (e) {
    if (window.innerWidth < 769
        && sidebar.classList.contains('mobile-open')
        && !sidebar.contains(e.target)
        && e.target !== toggle) {
      sidebar.classList.remove('mobile-open');
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= 769) {
      sidebar.classList.remove('mobile-open');
    }
  });
})();

// ---------------------------------------------------------------------------
// Flash auto-dismiss
// ---------------------------------------------------------------------------
(function () {
  document.querySelectorAll('#flash-container .alert').forEach(function (el) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      if (bsAlert) bsAlert.close();
    }, 6000);
  });
})();

// ---------------------------------------------------------------------------
// Confirm dialogs — data-confirm attribute
// ---------------------------------------------------------------------------
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-confirm]');
  if (!btn) return;
  const msg = btn.dataset.confirm || 'Are you sure?';
  if (!confirm(msg)) e.preventDefault();
});

// ---------------------------------------------------------------------------
// AJAX form helper — forms with data-ajax="true"
// Submits via fetch and shows a Bootstrap toast on response.
// ---------------------------------------------------------------------------
(function () {
  document.querySelectorAll('form[data-ajax="true"]').forEach(function (form) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = form.querySelector('[type="submit"]');
      const origText = btn ? btn.innerHTML : '';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading…';
      }

      try {
        const resp = await fetch(form.action, {
          method: form.method.toUpperCase() || 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          body: new FormData(form),
        });
        const data = await resp.json();
        BnkApp.toast(data.success ? 'success' : 'danger', data.message || (data.success ? 'Done.' : 'Error.'));
        if (data.success && form.dataset.reload === 'true') {
          setTimeout(() => location.reload(), 800);
        }
        if (data.success && form.dataset.redirect) {
          setTimeout(() => location.assign(form.dataset.redirect), 800);
        }
      } catch (err) {
        BnkApp.toast('danger', 'An unexpected error occurred.');
      } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = origText; }
      }
    });
  });
})();

// ---------------------------------------------------------------------------
// Global BnkApp namespace — utility functions
// ---------------------------------------------------------------------------
window.BnkApp = {

  /**
   * Show a Bootstrap 5 toast in the top-right corner.
   * @param {'success'|'danger'|'warning'|'info'} type
   * @param {string} message
   */
  toast: function (type, message) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container position-fixed top-0 end-0 p-3';
      container.style.zIndex = '9999';
      document.body.appendChild(container);
    }

    const icons = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const icon  = icons[type] || 'info-circle-fill';

    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi bi-${icon}"></i> ${BnkApp.escapeHtml(message)}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;

    container.appendChild(el);
    const bsToast = new bootstrap.Toast(el, { delay: 4000 });
    bsToast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  },

  /**
   * Escape HTML for safe insertion.
   */
  escapeHtml: function (str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
  },

  /**
   * Copy text to clipboard and show a brief toast.
   */
  copyToClipboard: function (text, label) {
    navigator.clipboard.writeText(text).then(function () {
      BnkApp.toast('success', (label || 'Value') + ' copied to clipboard.');
    }).catch(function () {
      BnkApp.toast('warning', 'Could not copy to clipboard.');
    });
  },

  /**
   * Send a PATCH / DELETE request via fetch (for quick action buttons).
   * @param {string} url
   * @param {'PATCH'|'DELETE'|'POST'} method
   * @param {object} body  — key/value pairs
   * @param {function} onSuccess
   */
  request: async function (url, method, body, onSuccess) {
    // Extract CSRF token from the first hidden _csrf_token on the page
    const csrfInput = document.querySelector('input[name="_csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    try {
      const formData = new FormData();
      if (body) Object.entries(body).forEach(([k, v]) => formData.append(k, v));
      formData.append('_csrf_token', csrfToken);

      const resp = await fetch(url, {
        method,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: formData,
      });
      const data = await resp.json();
      BnkApp.toast(data.success ? 'success' : 'danger', data.message || 'Done.');
      if (data.success && typeof onSuccess === 'function') onSuccess(data);
    } catch (err) {
      BnkApp.toast('danger', 'Request failed. Please try again.');
    }
  },
};

// ---------------------------------------------------------------------------
// Notification badge update (poll every 60 s)
// ---------------------------------------------------------------------------
(function () {
  const badge = document.getElementById('notif-count');
  if (!badge) return;

  async function fetchCount() {
    try {
      const r = await fetch('/notifications/count', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!r.ok) return;
      const d = await r.json();
      if (d.count > 0) {
        badge.textContent = d.count > 99 ? '99+' : d.count;
        badge.style.display = '';
      } else {
        badge.style.display = 'none';
      }
    } catch (_) { /* silent */ }
  }

  fetchCount();
  setInterval(fetchCount, 60000);
})();

// ---------------------------------------------------------------------------
// Data-table search filter (client-side, for small tables)
// ---------------------------------------------------------------------------
(function () {
  document.querySelectorAll('[data-table-filter]').forEach(function (input) {
    const tableId = input.dataset.tableFilter;
    const table   = document.getElementById(tableId);
    if (!table) return;

    input.addEventListener('input', function () {
      const q = input.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });
})();

// ---------------------------------------------------------------------------
// Numeric formatter (runs on elements with data-format="money")
// ---------------------------------------------------------------------------
(function () {
  const formatter = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'EUR' });
  document.querySelectorAll('[data-format="money"]').forEach(function (el) {
    const val = parseFloat(el.dataset.value);
    if (!isNaN(val)) el.textContent = formatter.format(val);
  });
})();

console.info('[BnkApp Admin] JS loaded.');
