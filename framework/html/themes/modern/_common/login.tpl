<!DOCTYPE html>
<html lang="{$LANG|default:'en'}"{if $IS_RTL} dir="rtl"{/if}>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Issabel - {$PAGE_NAME}</title>

  {* Modo claro/oscuro: misma clave y resolución que modern.js, y antes de los
     CSS para que no haya destello. Sin JS la página queda en claro. *}
  <script>
    (function () {
      var mode = 'auto';
      try { mode = localStorage.getItem('issabel-theme') || 'auto'; } catch (e) { mode = 'auto'; }
      var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
      var resolve = function (m) { return m === 'auto' ? (mq && mq.matches ? 'dark' : 'light') : m; };
      document.documentElement.setAttribute('data-bs-theme', resolve(mode));
      window.issabelLoginTheme = { mode: mode, mq: mq, resolve: resolve };
    })();
  </script>

  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/tabler.min.css">
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/tabler-icons.min.css">
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/inter.css">
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/modern.css?v={$ISSABEL_THEME_ASSET_VERSION}">

  {$HEADER_LIBS_JQUERY}
</head>
<body class="iss-login {$BODYPARAMS}">

  {* Franja superior: blanca en claro y casi negra en oscuro, del alto del logo
     animado (Lottie), con el triángulo centrado que se introduce en el morado
     (como Issabel 5). *}
  <div class="iss-login__hero">
    <div id="animIssabel" class="iss-login__anim">
      <noscript><img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo_color.png" alt="Issabel" class="iss-login__logo"></noscript>
    </div>
  </div>

  {* Resto de la pantalla: morado de marca, con el formulario. *}
  <div class="iss-login__panel">
    <div class="iss-login__card">
      {if !empty($WELCOME)}<h1 class="iss-login__title">{$WELCOME}</h1>{/if}

      {if !empty($LOGIN_INCORRECT)}
      <div class="alert alert-danger" role="alert">{$LOGIN_INCORRECT}</div>
      {/if}

      <form method="post">
        {$ISSABEL_CSRF_FIELD}
        <div class="mb-3">
          <label class="form-label" for="input_user">{$USERNAME}</label>
          <div class="input-group">
            <span class="input-group-text"><i class="ti ti-user"></i></span>
            <input type="text" class="form-control" name="input_user" id="input_user" placeholder="{$USERNAME}" autocomplete="off" autofocus>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="input_pass">{$PASSWORD}</label>
          <div class="input-group">
            <span class="input-group-text"><i class="ti ti-key"></i></span>
            <input type="password" class="form-control" name="input_pass" id="input_pass" placeholder="{$PASSWORD}" autocomplete="off">
          </div>
        </div>

        <div class="mb-3">
          <button type="submit" class="btn btn-primary w-100" name="submit_login">
            <i class="ti ti-login me-1"></i>{$SUBMIT}
          </button>
        </div>
      </form>

      <div class="iss-login__foot">
        <a href="http://www.issabel.org" target="_blank">Issabel</a> {$ISSABEL_LICENSED}
        <a href="http://www.opensource.org/licenses/gpl-license.php" target="_blank">GPL</a>. 2006 - {$currentyear}.
      </div>
    </div>
  </div>

  <script src="{$WEBPATH}themes/{$THEMENAME}/js/lottie.min.js?v={$ISSABEL_THEME_ASSET_VERSION}"></script>
  <script>
    (function () {
      var el = document.getElementById('animIssabel');
      if (!el) return;

      var base = '{$WEBPATH}themes/{$THEMENAME}/images/animIssabel';
      var assetVersion = '{$ISSABEL_THEME_ASSET_VERSION}';
      var anim = null;

      function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
      }
      function useFallback() {
        el.innerHTML = '<img src="{$WEBPATH}themes/{$THEMENAME}/images/' +
          (isDark() ? 'issabel_logo.png' : 'issabel_logo_color.png') +
          '" alt="Issabel" class="iss-login__logo">';
      }
      function load() {
        if (!window.lottie) { useFallback(); return; }
        if (anim) { anim.destroy(); anim = null; }
        el.innerHTML = '';
        anim = lottie.loadAnimation({
          container: el,
          renderer: 'svg',
          loop: false,
          autoplay: true,
          path: base + (isDark() ? '-dark' : '') + '.json?v=' + assetVersion
        });
        anim.setSpeed(1.5);
        anim.addEventListener('data_failed', useFallback);
      }

      load();

      {* Con la preferencia en "auto", si el navegador cambia de modo se
         repinta el tema y se recarga el logo con la variante correcta. *}
      var t = window.issabelLoginTheme;
      if (t && t.mq && t.mq.addEventListener) {
        t.mq.addEventListener('change', function () {
          var next = t.resolve(t.mode);
          if (next === document.documentElement.getAttribute('data-bs-theme')) return;
          document.documentElement.setAttribute('data-bs-theme', next);
          load();
        });
      }
    })();
  </script>
</body>
</html>
