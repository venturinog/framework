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
  {$CONTENT}
</body>
</html>
