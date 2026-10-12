{if $userExtension_success == 1}
{literal}
<script type="text/javascript">
if (window.opener && !window.opener.closed) {
    window.opener.location.reload();
}
window.close();
</script>
{/literal}
{else}
<form class="iss-form" method="POST" autocomplete="off">
  <!-- Barra de acciones: suspensión visual "card header" del formulario -->
  <div class="iss-form__toolbar">
    {if $mode ne 'view'}
      <span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>
    {/if}
    <div class="iss-form__buttons">
      <button class="button" type="submit" name="save" value="{$SAVE}"><i class="ti ti-device-floppy"></i> {$SAVE}</button>
      {if $editUserExtension ne 'yes'}<input class="button" type="submit" name="cancel" value="{$CANCEL}">{/if}
    </div>
  </div>

  <!-- Sección principal -->
  <section class="iss-card">
    <header class="iss-card__header"><i class="ti ti-user-circle"></i> {$LBL_CORE_FIELDS}</header>
    <div class="iss-card__body iss-formgrid">
      <div class="iss-field">
        <span class="iss-field__label">{$name.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$name.INPUT}</span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$description.LABEL}</span>
        <span class="iss-field__control">{$description.INPUT}</span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$password1.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$password1.INPUT}</span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$password2.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$password2.INPUT}</span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$group.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$group.INPUT}</span>
      </div>
    </div>
  </section>

  {$PLUGIN_CONTENT}

  <input type="hidden" name="id_user" value="{$id_user}">
</form>
{/if}
