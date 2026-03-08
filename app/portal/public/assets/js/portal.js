/* ==========================================================================
   BnkApp Customer Portal — JavaScript
   ========================================================================== */
'use strict';

/* ── Sidebar toggle ──────────────────────────────────────────────────────── */
(function () {
  const sidebar  = document.getElementById('portal-sidebar');
  const toggle   = document.getElementById('portalSidebarToggle');
  const overlay  = document.getElementById('sbOverlay');
  if (!sidebar || !toggle) return;

  function openMobile() {
    sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeMobile() {
    sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
  }
  function toggleDesktop() {
    sidebar.classList.toggle('collapsed');
  }

  toggle.addEventListener('click', function () {
    if (window.innerWidth <= 768) {
      sidebar.classList.contains('mobile-open') ? closeMobile() : openMobile();
    } else {
      toggleDesktop();
    }
  });

  if (overlay) overlay.addEventListener('click', closeMobile);

  // Close mobile sidebar on nav link click
  sidebar.querySelectorAll('.sb-link').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.innerWidth <= 768) closeMobile();
    });
  });
})();

/* ── Flash auto-dismiss ──────────────────────────────────────────────────── */
(function () {
  document.querySelectorAll('.alert-dismissible').forEach(function (el) {
    setTimeout(function () {
      const inst = bootstrap.Alert.getOrCreateInstance(el);
      if (inst) inst.close();
    }, 6000);
  });
})();

/* ── Confirm dialogs ─────────────────────────────────────────────────────── */
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-confirm]');
  if (btn && !confirm(btn.dataset.confirm || 'Are you sure?')) e.preventDefault();
});

/* ── Copy IBAN / text ────────────────────────────────────────────────────── */
document.querySelectorAll('[data-copy]').forEach(function (btn) {
  btn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    navigator.clipboard.writeText(btn.dataset.copy).then(function () {
      const icon = btn.querySelector('i');
      const orig = icon ? icon.className : btn.innerHTML;
      if (icon) icon.className = 'bi bi-check-lg';
      else btn.innerHTML = '<i class="bi bi-check-lg"></i>';
      setTimeout(function () {
        if (icon) icon.className = orig;
        else btn.innerHTML = orig;
      }, 1800);
    }).catch(function () {
      // Fallback for non-secure contexts
      const ta = document.createElement('textarea');
      ta.value = btn.dataset.copy;
      ta.style.position = 'fixed';
      ta.style.opacity  = '0';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    });
  });
});

/* ── Password toggle ─────────────────────────────────────────────────────── */
document.querySelectorAll('[data-toggle-pwd]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const inp  = btn.closest('.input-group').querySelector('input[type="password"], input[type="text"]');
    const icon = btn.querySelector('i');
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

/* ── SEPA beneficiary quick-fill ─────────────────────────────────────────── */
(function () {
  const benSelect = document.getElementById('benSelect');
  if (!benSelect) return;
  benSelect.addEventListener('change', function () {
    const opt       = benSelect.options[benSelect.selectedIndex];
    const ibanField = document.getElementById('creditorIban');
    const nameField = document.getElementById('creditorName');
    if (ibanField) ibanField.value = opt.value;
    if (nameField) nameField.value = opt.dataset.name || '';
  });
})();

/* ── Account balance hint ────────────────────────────────────────────────── */
(function () {
  document.querySelectorAll('select[name="from_account_id"]').forEach(function (sel) {
    const form = sel.closest('form');
    if (!form) return;
    const hint = form.querySelector('[data-balance-hint]');
    if (!hint) return;

    function updateHint() {
      const opt = sel.options[sel.selectedIndex];
      if (opt && opt.dataset.balance !== undefined) {
        const bal = parseFloat(opt.dataset.balance);
        const cur = opt.dataset.currency || 'EUR';
        try {
          hint.textContent = 'Available: ' + new Intl.NumberFormat('en-GB', { style:'currency', currency:cur }).format(bal);
        } catch (_) {
          hint.textContent = 'Available: ' + bal.toFixed(2) + ' ' + cur;
        }
        hint.style.display = '';
      } else {
        hint.style.display = 'none';
      }
    }
    sel.addEventListener('change', updateHint);
    updateHint();
  });
})();

/* ── Number counter animation ────────────────────────────────────────────── */
(function () {
  function animateCounter(el) {
    const text = el.textContent.trim();
    // Extract numeric part (e.g. "€1,234.56" → 1234.56)
    const match = text.match(/[\d,]+\.?\d*/);
    if (!match) return;
    const target = parseFloat(match[0].replace(/,/g,''));
    if (isNaN(target) || target === 0) return;
    const prefix = text.slice(0, text.indexOf(match[0]));
    const suffix = text.slice(text.indexOf(match[0]) + match[0].length);
    const duration = 900;
    const start = performance.now();

    function step(now) {
      const progress = Math.min((now - start) / duration, 1);
      const ease     = 1 - Math.pow(1 - progress, 3);
      const value    = target * ease;
      // Reformat nicely
      try {
        el.textContent = prefix + new Intl.NumberFormat('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + suffix;
      } catch (_) {
        el.textContent = prefix + value.toFixed(2) + suffix;
      }
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = text; // Restore exact original
    }
    requestAnimationFrame(step);
  }

  const heroEl = document.getElementById('heroBalance');
  if (heroEl) {
    // Only animate if IntersectionObserver available
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { animateCounter(heroEl); obs.disconnect(); }
        });
      }, { threshold: .5 });
      obs.observe(heroEl);
    } else {
      animateCounter(heroEl);
    }
  }
})();

/* ── Smooth page load indicator ──────────────────────────────────────────── */
(function () {
  const bar = document.createElement('div');
  bar.id = 'page-progress';
  bar.style.cssText = 'position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,#2563eb,#7c3aed);z-index:9999;transition:width .3s ease;width:0;';
  document.body.prepend(bar);
  bar.style.width = '70%';
  window.addEventListener('load', function () {
    bar.style.width = '100%';
    setTimeout(function () { bar.style.opacity = '0'; }, 300);
  });
})();

/* ── Active nav highlighting ─────────────────────────────────────────────── */
(function () {
  const path = window.location.pathname;
  document.querySelectorAll('.sb-link').forEach(function (a) {
    const href = a.getAttribute('href');
    if (!href) return;
    if (href === '/' && path === '/') a.classList.add('active');
    else if (href !== '/' && path.startsWith(href)) a.classList.add('active');
  });
})();

console.info('[BnkApp Portal] UI v2.0 loaded.');
