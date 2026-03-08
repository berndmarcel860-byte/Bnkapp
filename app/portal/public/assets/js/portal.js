/* ==========================================================================
   BnkApp Customer Portal — JavaScript
   ========================================================================== */

'use strict';

// Sidebar toggle
(function () {
  const sidebar = document.getElementById('portal-sidebar');
  const toggle  = document.getElementById('portalSidebarToggle');
  if (!sidebar || !toggle) return;

  toggle.addEventListener('click', function () {
    if (window.innerWidth < 769) {
      sidebar.classList.toggle('mobile-open');
    } else {
      sidebar.classList.toggle('collapsed');
    }
  });

  document.addEventListener('click', function (e) {
    if (window.innerWidth < 769 && sidebar.classList.contains('mobile-open')
        && !sidebar.contains(e.target) && e.target !== toggle) {
      sidebar.classList.remove('mobile-open');
    }
  });
})();

// Flash auto-dismiss
(function () {
  document.querySelectorAll('.alert-dismissible').forEach(function (el) {
    setTimeout(function () {
      const inst = bootstrap.Alert.getOrCreateInstance(el);
      if (inst) inst.close();
    }, 5000);
  });
})();

// Confirm dialogs
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-confirm]');
  if (btn && !confirm(btn.dataset.confirm || 'Are you sure?')) e.preventDefault();
});

// Copy IBAN button
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    navigator.clipboard.writeText(btn.dataset.copy).then(function () {
      const orig = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check-lg"></i>';
      setTimeout(function () { btn.innerHTML = orig; }, 1500);
    });
  });
});

// Password visibility toggle (replaces inline onclick handlers)
document.querySelectorAll('[data-toggle-pwd]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var inp = btn.closest('.input-group').querySelector('input[type="password"], input[type="text"]');
    var icon = btn.querySelector('i');
    if (!inp) return;
    if (inp.type === 'password') {
      inp.type = 'text';
      if (icon) icon.className = 'bi bi-eye-slash';
    } else {
      inp.type = 'password';
      if (icon) icon.className = 'bi bi-eye';
    }
  });
});

// SEPA quick-select beneficiary fill
(function () {
  var benSelect = document.getElementById('benSelect');
  if (!benSelect) return;
  benSelect.addEventListener('change', function () {
    var opt = benSelect.options[benSelect.selectedIndex];
    var ibanField = document.getElementById('creditorIban');
    var nameField = document.getElementById('creditorName');
    if (ibanField) ibanField.value = opt.value;
    if (nameField) nameField.value = opt.dataset.name || '';
  });
})();

console.info('[BnkApp Portal] JS loaded.');
