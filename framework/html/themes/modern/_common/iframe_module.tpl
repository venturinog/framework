<!DOCTYPE html>
<html lang="{$LANG|default:'en'}"{if $IS_RTL} dir="rtl"{/if} data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="issabel-csrf-token" content="{$ISSABEL_CSRF_TOKEN}">
  <script>
    (function () {
      try {
        var mode = localStorage.getItem('issabel-theme') || 'auto';
        var dark = mode === 'auto'
          ? window.matchMedia('(prefers-color-scheme: dark)').matches
          : mode === 'dark';
        document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
      } catch (e) {}
    })();
  </script>
  {* El CSS de jQuery UI va antes que el del tema: el tema Modern lo
     remapea en modern-compat.css, y para que esas reglas ganen tienen que
     cargarse despues. *}
  {$HEADER_LIBS_JQUERY}
  {include file="_common/_head_assets.tpl"}
  <script src="{$WEBPATH}libs/js/base.js"></script>
  <script src="{$WEBPATH}libs/js/iframe.js"></script>
  <script src="{$WEBPATH}libs/js/csrf.js"></script>
  {$HEADER}
  {$HEADER_MODULES|default:''}
  <script src="{$WEBPATH}themes/{$THEMENAME}/js/tabs-bridge.js?v={$ISSABEL_TABS_ASSET_VERSIONS.tabsBridgeJs}"></script>
</head>
<body class="iss-body iss-iframe-body">
  <input type="hidden" id="issabel_framework_module_id" value="{$ISSABEL_MODULE_ID|escape:html}">

  {* Aviso del módulo. La shell completa (index.tpl) pinta mb_title/mb_message,
     pero bajo el layout de pestañas el módulo vive en este iframe, que es un
     documento aparte: sin esto el módulo asignaba el aviso y nunca se veía. *}
  {if !empty($mb_message)}
  <div class="iss-alert iss-alert--error" id="message_error">
    {if !empty($mb_title)}
    <div class="iss-alert__title">{$mb_title}</div>
    {/if}
    <div class="iss-alert__dismiss"><i class="ti ti-x" onclick="hide_message_error();"></i></div>
    <div class="iss-alert__content">{$mb_message}</div>
  </div>
  {/if}

  {* Popup box global. ShowModalPopUP() de base.js opera sobre estos nodos,
     y un modulo legacy que la llame desde dentro del iframe (addons, cloud
     login del registro) quedaba en silencio porque la maqueta completa del
     index.tpl si los trae y este documento del iframe, no. *}
  <div class="neo-modal-issabel-popup-box">
      <div class="neo-modal-issabel-popup-title"></div>
      <div class="neo-modal-issabel-popup-close"><i class="ti ti-x"></i></div>
      <div class="neo-modal-issabel-popup-content"></div>
  </div>
  <div class="neo-modal-issabel-popup-blockmask"></div>

  {$CONTENT}
</body>
</html>
