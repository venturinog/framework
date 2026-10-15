<!DOCTYPE html>
<html lang="{$LANG|default:'en'}"{if $IS_RTL} dir="rtl"{/if} data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="issabel-csrf-token" content="{$ISSABEL_CSRF_TOKEN}">
  <title>Issabel</title>

  {* Anti-FOUC del modo oscuro (default: auto). Se aplica antes de pintar. *}
  <script>
    (function () {
      try {
        var m = localStorage.getItem('issabel-theme') || 'auto';
        var d = (m === 'auto') ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : m;
        document.documentElement.setAttribute('data-bs-theme', d);
      } catch (e) {}
    })();
  </script>

  {include file="_common/_head_assets.tpl"}
  <link rel="stylesheet" href="{$WEBPATH}themes/{$THEMENAME}/css/tabs.css?v={$ISSABEL_TABS_ASSET_VERSIONS.tabsCss}">

  {$HEADER_LIBS_JQUERY}
  <script src="libs/js/base.js"></script>
  <script src="libs/js/iframe.js"></script>
  <script src="libs/js/csrf.js"></script>

  {$HEADER}
  {$HEADER_MODULES|default:''}
</head>
<body class="iss-body {$BODYPARAMS}">
<div class="page">
    {$MENU} {* viene de _common/_menu.tpl; ABRE el bloque .iss-main *}

    {if !empty($mb_message)}
    <div class="iss-alert-error" id="message_error">
        {if !empty($mb_title)}
        <div class="iss-alert-error__title"><b>&nbsp;{$mb_title}</b></div>
        {/if}
        <div class="iss-alert-error__dismiss"><i class="ti ti-x" onclick="hide_message_error();"></i></div>
        <div class="iss-alert-error__content" {if empty($mb_title)}style="margin-left: 0;"{/if}>{$mb_message}</div>
    </div>
    {/if}

    {if !empty($ISSABEL_PENDING_NETWORK)}
    <div class="iss-alert-warning" id="issabel_pending_network" style="border:1px solid #d9a000;background:#fff6d9;padding:8px 12px;margin:8px 12px;">
        <i class="ti ti-alert-triangle"></i> {$ISSABEL_PENDING_NETWORK}
        <a href="?menu=network_parameters">{$ISSABEL_PENDING_NETWORK_LINK}</a>
    </div>
    {/if}

    <div id="iss-tabpanes" class="iss-tabpanes" hidden></div>
    </div><!-- /.iss-content (abierto en _menu.tpl) -->

        <footer class="iss-footer">
            <a href="http://www.issabel.org" target="_blank">Issabel</a> {$ISSABEL_LICENSED}
            <a href="http://www.opensource.org/licenses/gpl-license.php" target="_blank">GPL</a>. 2006 - {$currentyear}.
        </footer>
    </div><!-- /.iss-main (abierto en _menu.tpl) -->
</div><!-- /.page -->

{* Popup box global *}
<div class="neo-modal-issabel-popup-box">
    <div class="neo-modal-issabel-popup-title"></div>
    <div class="neo-modal-issabel-popup-close"></div>
    <div class="neo-modal-issabel-popup-content"></div>
</div>
<div class="neo-modal-issabel-popup-blockmask"></div>

{if $ISSABEL_PANELS}
<div id="chat" class="fixed">
    <div class="chat-inner">
        <h2 class="chat-header">
            <a href="#" class="chat-close"><i class="ti ti-x"></i></a>
            <i class="ti ti-users"></i>
            <span id="panel-header-text">{$LBL_ISSABEL_PANELS_SIDEBAR|escape:html}</span>
        </h2>
        <div id="issabel-panels" class="panel-group joined">
            {foreach from=$ISSABEL_PANELS key=panelname item=paneldata name=issabelpanel}
            <div class="panel">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a data-bs-toggle="collapse" data-parent="#issabel-panels" href="#issabel-panel-{$panelname}">
                            {if $paneldata.iconclass}<i class="{$paneldata.iconclass}"></i>{elseif $paneldata.icon}<img alt="" src="{$paneldata.icon}" width="15" />{else}<i class="ti ti-file"></i>{/if}
                            <span>{$paneldata.title|escape:html}</span>
                        </a>
                    </h4>
                </div>
                <div id="issabel-panel-{$panelname}" class="panel-collapse collapse{if $smarty.foreach.issabelpanel.first} show{/if}">
                    <div class="panel-body">{$paneldata.content}</div>
                </div>
            </div>
            {/foreach}
        </div>
    </div>
</div>
{/if}

<!-- Bottom scripts -->
<script src="{$WEBPATH}themes/{$THEMENAME}/js/tabler.min.js"></script>
<script src="{$WEBPATH}themes/{$THEMENAME}/js/modern.js?v={$ISSABEL_TABS_ASSET_VERSIONS.modernJs}"></script>
<script>window.ISSABEL_TABS_CONFIG = {$ISSABEL_TABS_CONFIG_JSON};</script>
<script src="{$WEBPATH}themes/{$THEMENAME}/js/tabs.js?v={$ISSABEL_TABS_ASSET_VERSIONS.tabsJs}"></script>
</body>
</html>
