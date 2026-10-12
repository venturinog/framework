<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <title>Issabel</title>
        {if $THEMENAME eq "modern"}
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/css/tabler.min.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/css/tabler-icons.min.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/css/font-awesome.min.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/css/inter.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/css/modern.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/css/modern-compat.css">
        {else}
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/styles.css">
        <link rel="stylesheet" href="{$path}themes/{$THEMENAME}/help.css">
        {/if}
        {$HEADER_LIBS_JQUERY}
        <script type="text/javascript" src="{$path}libs/js/base.js"></script>
        {$HEADER}
        {$HEADER_MODULES|default:''}
    </head>
    <body class="{if $THEMENAME eq "modern"}iss-popup{else}iss-body{/if}">
    {if $THEMENAME eq "modern"}
        <div class="iss-popup-container" style="padding: 18px; background: var(--iss-body-bg); min-height: 100vh; box-sizing: border-box; color: var(--iss-text);">
{if !empty($mb_message)}
<div class="div_msg_errors" id="message_error">
    <div style="height:24px">
        <div class="div_msg_errors_title" style="padding-left:5px"><b style="color:red;">&nbsp;{$mb_title}</b></div>
        <div class="div_msg_errors_dismiss"><input type="button" onclick="hide_message_error();" value="{$md_message_title}"/></div>
    </div>
    <div style="padding:2px 10px 2px 10px">{$mb_message}</div>
</div>
{/if}
          {$CONTENT}
        </div>
    {elseif $THEMENAME eq "elastixneo"}
      <div>
        <div class="elxneo-module-title">
            <div class="name-left"></div>
            <span class="name">
          {if $icon ne null}
          <img src="{$icon}" width="22" height="22" align="absmiddle" />
          {/if}
          &nbsp;{$title}</span>
            <div class="name-right"></div>
          </div>
      <div id="elxneo-content">
{if !empty($mb_message)}
<div class="div_msg_errors" id="message_error">
    <div style="height:24px">
        <div class="div_msg_errors_title" style="padding-left:5px"><b style="color:red;">&nbsp;{$mb_title}</b></div>
        <div class="div_msg_errors_dismiss"><input type="button" onclick="hide_message_error();" value="{$md_message_title}"/></div>
    </div>
    <div style="padding:2px 10px 2px 10px">{$mb_message}</div>
</div>
{/if}
          {$CONTENT}
       </div>
    {else}
{if !empty($mb_message)}
    <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center" class="message_board">
      <tbody id="table_error">
      <tr>
        <tr>
            <td valign="middle" class="mb_title">&nbsp;{$mb_title}</td>
        </tr>
        <tr>
            <td valign="middle" class="mb_message">{$mb_message}</td>
        </tr>
    </tbody></table>
{/if}
    <div class="moduleTitle">
      &nbsp;&nbsp;<img src="{$icon}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}
    </div>
    {$CONTENT}
{/if}
    </body>
</html>
