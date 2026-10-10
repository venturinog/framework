/* =========================================================================
   modern.js — comportamiento del tema "Modern" (Issabel 6)  [Fase 0]
   - Modo oscuro (auto / claro / oscuro) con persistencia en localStorage
   - Sidebar: off-canvas en móvil, colapso en escritorio
   - Buscador de módulos + acordeón
   - readNoti() (portado de neon-custom.js)
   Requiere jQuery (cargado global por el framework).
   ========================================================================= */
(function () {
  'use strict';

  /* ---------------------------- Modo oscuro ---------------------------- */
  var KEY = 'issabel-theme';
  var mq = window.matchMedia('(prefers-color-scheme: dark)');

  function stored() {
    try { return localStorage.getItem(KEY) || 'auto'; } catch (e) { return 'auto'; }
  }
  function resolve(mode) {
    return mode === 'auto' ? (mq.matches ? 'dark' : 'light') : mode;
  }
  function paint(mode) {
    document.documentElement.setAttribute('data-bs-theme', resolve(mode));
    document.querySelectorAll('[data-theme-set]').forEach(function (b) {
      var on = b.getAttribute('data-theme-set') === mode;
      b.classList.toggle('active', on);
      b.setAttribute('aria-pressed', String(on));
    });
  }
  function setMode(mode) {
    try { localStorage.setItem(KEY, mode); } catch (e) {}
    paint(mode);
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-theme-set]') : null;
    if (b) { e.preventDefault(); setMode(b.getAttribute('data-theme-set')); }
  });
  mq.addEventListener('change', function () { if (stored() === 'auto') paint('auto'); });
  paint(stored());

  /* ------------------------------ Sidebar ------------------------------ */
  function openOnMobile() { return window.matchMedia('(max-width: 991px)').matches; }

  var burger = document.getElementById('issBurger');
  if (burger) {
    burger.addEventListener('click', function () {
      if (openOnMobile()) document.body.classList.toggle('iss-nav-open');
      else document.body.classList.toggle('iss-collapsed');
    });
  }
  document.body.addEventListener('click', function (e) {
    if (e.target === document.body && document.body.classList.contains('iss-nav-open')) {
      document.body.classList.remove('iss-nav-open');
    }
  });
  document.querySelectorAll('.iss-nav a').forEach(function (a) {
    a.addEventListener('click', function () { if (openOnMobile()) document.body.classList.remove('iss-nav-open'); });
  });

  /* ------------------------- Acordeón del menú ------------------------- */
  function setExpanded(btn, open) {
    btn.setAttribute('aria-expanded', String(open));
    var t = document.querySelector(btn.dataset.target);
    if (t) t.hidden = !open;
  }
  // 1er nivel: exclusivos entre sí
  document.querySelectorAll('.iss-nav__toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll('.iss-nav__toggle').forEach(function (b) { if (b !== btn) setExpanded(b, false); });
      setExpanded(btn, !open);
    });
  });
  // 2º nivel: exclusivos entre hermanos (mismo grupo)
  document.querySelectorAll('.iss-nav__subtoggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') === 'true';
      var group = btn.closest('.iss-nav__sub');
      if (group) group.querySelectorAll('.iss-nav__subtoggle').forEach(function (b) { if (b !== btn) setExpanded(b, false); });
      setExpanded(btn, !open);
    });
  });

  /* --------------------- Buscador de módulos --------------------- */
  var menuSearch = document.getElementById('menuSearch');
  if (menuSearch) {
    menuSearch.addEventListener('input', function () {
      var q = this.value.toLowerCase();
      document.querySelectorAll('.iss-nav__group').forEach(function (g) {
        g.style.display = g.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }

  /* --------------------- Notificaciones --------------------- */
  window.readNoti = function (id) {
    if (!window.jQuery) return;
    window.jQuery.get('index.php', {
      menu: '_issabelutils', action: 'readNoti', rawmode: 'yes', id_noti: id
    }, function () {
      var bell = document.getElementById('notibell');
      if (bell) bell.classList.remove('faa-shake');
      var item = document.getElementById('notiitem' + id);
      if (item) item.style.display = 'none';
    });
  };
})();
