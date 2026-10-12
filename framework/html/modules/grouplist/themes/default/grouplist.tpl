<form class="iss-form" method="POST" action="?menu=grouplist" autocomplete="off">
  <div class="iss-form__toolbar">
    {if $mode ne 'view'}<span class="iss-form__hint"><span class="iss-required">*</span> {$REQUIRED_FIELD}</span>{/if}
    <div class="iss-form__buttons">
      {if $mode eq 'input'}
      <input class="button" type="submit" name="submit_save_group" value="{$SAVE}">
      <input class="button" type="submit" name="cancel" value="{$CANCEL}">
      {elseif $mode eq 'edit'}
      <input class="button" type="submit" name="submit_apply_changes" value="{$APPLY_CHANGES}">
      <input class="button" type="submit" name="cancel" value="{$CANCEL}">
      {else}
      <input class="button" type="submit" name="edit" value="{$EDIT}">
      <input class="button" type="submit" name="delete" value="{$DELETE}" onClick="return confirmSubmit('{$CONFIRM_CONTINUE}')">
      {/if}
    </div>
  </div>
  <section class="iss-card">
    <div class="iss-card__body iss-formgrid">
      <div class="iss-field">
        <span class="iss-field__label">{$group.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$group.INPUT}</span>
      </div>
      <div class="iss-field iss-field--full">
        <span class="iss-field__label">{$description.LABEL}{if $mode ne 'view'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$description.INPUT}</span>
      </div>
    </div>
  </section>
  <input type="hidden" name="id_group" value="{$id_group}">
</form>
