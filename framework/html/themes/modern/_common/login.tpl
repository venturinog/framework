<!DOCTYPE html>
<html lang="{$LANG|default:'en'}"{if $IS_RTL} dir="rtl"{/if} data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Issabel - {$PAGE_NAME}</title>

  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/tabler.min.css">
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/tabler-icons.min.css">
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/inter.css">
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/modern.css">

  {$HEADER_LIBS_JQUERY}
</head>
<body class="iss-login {$BODYPARAMS}">

  {* Mitad superior: blanca, con el logo animado (Lottie) y el triángulo
     centrado que se introduce en la mitad morada (como Issabel 5). *}
  <div class="iss-login__hero">
    <div id="animIssabel" class="iss-login__anim">
      <noscript><img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo_color.png" alt="Issabel" class="iss-login__logo"></noscript>
    </div>
  </div>

  {* Mitad inferior: morado de marca, con el formulario. *}
  <div class="iss-login__panel">
    <div class="iss-login__card">
      {if !empty($WELCOME)}<h1 class="iss-login__title">{$WELCOME}</h1>{/if}

      {if !empty($LOGIN_INCORRECT)}
      <div class="alert alert-danger" role="alert">{$LOGIN_INCORRECT}</div>
      {/if}

      <form method="post">
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

  <script src="{$WEBPATH}themes/{$THEMENAME}/js/lottie.min.js"></script>
  <script>
    (function () {
      var el = document.getElementById('animIssabel');
      if (!el) return;
      if (window.lottie) {
        lottie.loadAnimation({
          container: el,
          renderer: 'svg',
          loop: false,
          autoplay: true,
          path: '{$WEBPATH}themes/{$THEMENAME}/images/animIssabel.json'
        }).setSpeed(1.5);
      } else {
        el.innerHTML = '<img src="{$WEBPATH}themes/{$THEMENAME}/images/issabel_logo_color.png" alt="Issabel" class="iss-login__logo">';
      }
    })();
  </script>
</body>
</html>
