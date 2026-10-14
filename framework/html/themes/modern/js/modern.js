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
    var resolvedTheme = resolve(mode);
    document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
    document.querySelectorAll('[data-theme-set]').forEach(function (b) {
      var on = b.getAttribute('data-theme-set') === mode;
      b.classList.toggle('active', on);
      b.setAttribute('aria-pressed', String(on));
    });
    window.dispatchEvent(new CustomEvent('issabel-theme-changed', {
      detail: { mode: mode, theme: resolvedTheme }
    }));
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
  window.addEventListener('storage', function (event) {
    if (event.key === KEY) paint(event.newValue || 'auto');
  });
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
    if (t) t.classList.toggle('expanded', open);
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

  /* --------------------- Buscador de módulos ---------------------
   * Filtra ítem por ítem (no el grupo entero): muestra solo los módulos
   * que coinciden por texto o por id de menú (href/data-target),
   * auto-expande los ancestros (grupo/subgrupo), resalta la coincidencia
   * y oculta los accesos rápidos mientras dura la búsqueda. Al vaciar el
   * campo se restaura el estado previo del menú. */
  var menuSearch = document.getElementById('menuSearch');
  var nav = document.querySelector('.iss-nav');
  if (menuSearch && nav) {
    var quickLinks   = nav.querySelectorAll('.iss-nav__quick');
    var shortcutList = nav.querySelector('.iss-nav__shortcut');
    var noResultsMsg = document.createElement('div');
    noResultsMsg.className = 'iss-nav__noresults';
    noResultsMsg.setAttribute('role', 'status');
    noResultsMsg.textContent = menuSearch.getAttribute('data-no-results') || 'No modules found';
    noResultsMsg.style.display = 'none';
    if (shortcutList) nav.insertBefore(noResultsMsg, shortcutList);
    else nav.appendChild(noResultsMsg);

    function normalizeText(s) {
      s = (s || '').toString().toLowerCase();
      try { s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) {}
      return s;
    }
    function escapeRegExp(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
    function showEl(el, visible) { if (el) el.style.display = visible ? '' : 'none'; }

    // El texto visible del ítem está en el <span> (toggles) o en el propio
    // enlace; el icono tabler es un <i> sin texto.
    function labelTarget(el) {
      if (!el) return null;
      return el.querySelector('span') || el;
    }
    function labelTargets() {
      var out = [];
      nav.querySelectorAll('.iss-nav__toggle, .iss-nav__subtoggle, .iss-nav__subinner > a, .iss-nav__thirdinner > a')
        .forEach(function (el) {
          var t = labelTarget(el);
          if (t && out.indexOf(t) === -1) out.push(t);
        });
      return out;
    }
    function highlight(el, raw) {
      var t = labelTarget(el);
      if (!t) return;
      if (t._issOrigHtml === undefined) t._issOrigHtml = t.innerHTML;
      t.innerHTML = t._issOrigHtml;
      if (!raw || t.children.length) return;
      var re = new RegExp('(' + escapeRegExp(raw) + ')', 'gi');
      if (re.test(t.textContent)) t.innerHTML = t.textContent.replace(re, '<mark>$1</mark>');
    }
    function unhighlight() {
      labelTargets().forEach(function (t) {
        if (t._issOrigHtml !== undefined) t.innerHTML = t._issOrigHtml;
      });
    }
    function matches(el, q) {
      if (!el) return false;
      var hay = normalizeText(el.textContent + ' ' + (el.getAttribute('href') || '') +
                              ' ' + (el.getAttribute('data-target') || ''));
      if (hay.indexOf(q) !== -1) return true;
      // Tolerante a separadores: "addressbook"/"address-book" → "Address Book".
      var qAlnum = q.replace(/[^a-z0-9]/g, '');
      if (!qAlnum) return false;
      return hay.replace(/[^a-z0-9]/g, '').indexOf(qAlnum) !== -1;
    }

    /* Estado previo a la búsqueda (display + acordeón) para restaurarlo. */
    var savedState = null;
    function saveState() {
      savedState = { display: [], expanded: [] };
      nav.querySelectorAll('.iss-nav__quick, .iss-nav__group, .iss-nav__subinner > a, ' +
                           '.iss-nav__subtoggle, .iss-nav__third, .iss-nav__thirdinner > a, .iss-nav__shortcut')
        .forEach(function (el) { savedState.display.push([el, el.style.display]); });
      nav.querySelectorAll('.iss-nav__toggle, .iss-nav__subtoggle').forEach(function (btn) {
        savedState.expanded.push([btn, btn.getAttribute('aria-expanded') === 'true']);
      });
    }
    function restoreState() {
      if (!savedState) return;
      savedState.display.forEach(function (p) { p[0].style.display = p[1]; });
      savedState.expanded.forEach(function (p) {
        var btn = p[0];
        var t = btn.dataset.target ? document.querySelector(btn.dataset.target) : null;
        btn.setAttribute('aria-expanded', String(p[1]));
        if (t) t.classList.toggle('expanded', p[1]);
      });
      savedState = null;
    }

    function applyFilter(q, raw) {
      quickLinks.forEach(function (a) { showEl(a, false); });
      showEl(shortcutList, false);
      showEl(noResultsMsg, false);
      unhighlight();

      var anyResult = false;
      nav.querySelectorAll('.iss-nav__group').forEach(function (g) {
        var toggle = g.querySelector('.iss-nav__toggle');
        var labelMatch = matches(toggle, q);
        var groupVisible = labelMatch;

        // Enlaces directos de 2º nivel
        g.querySelectorAll('.iss-nav__subinner > a').forEach(function (a) {
          var m = labelMatch || matches(a, q);
          showEl(a, m);
          if (m) { groupVisible = true; highlight(a, raw); }
        });

        // Ramas de 3er nivel (subtoggle + su .iss-nav__third)
        g.querySelectorAll('.iss-nav__subtoggle').forEach(function (st) {
          var stMatch = labelMatch || matches(st, q);
          var third = st.dataset.target ? document.querySelector(st.dataset.target) : null;
          var branchVisible = stMatch;
          if (third) {
            third.querySelectorAll('.iss-nav__thirdinner > a').forEach(function (a) {
              var m = stMatch || matches(a, q);
              showEl(a, m);
              if (m) { branchVisible = true; highlight(a, raw); }
            });
          }
          showEl(st, branchVisible);
          showEl(third, branchVisible);
          setExpanded(st, branchVisible);
          if (stMatch) highlight(st, raw);
          if (branchVisible) groupVisible = true;
        });

        if (labelMatch) highlight(toggle, raw);
        showEl(g, groupVisible);
        setExpanded(toggle, groupVisible);
        if (groupVisible) anyResult = true;
      });

      showEl(noResultsMsg, !anyResult);
    }

    menuSearch.addEventListener('input', function () {
      var raw = this.value.trim();
      var q = normalizeText(raw);
      if (q === '') { restoreState(); unhighlight(); showEl(noResultsMsg, false); return; }
      if (!savedState) saveState();
      applyFilter(q, raw);
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
