<form id="groupForm" class="iss-form" method="POST" action="?menu=grouplist" autocomplete="off">
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
  {if $mode eq 'edit'}<div class="iss-reveal">{/if}
  <section class="iss-card">
    {if $mode eq 'view'}
    <div class="iss-card__body iss-formgrid iss-formgrid--readonly">
      <div class="iss-field">
        <span class="iss-field__label">{$group.LABEL}</span>
        <span class="iss-field__control iss-field__value{if $GROUP_VIEW.group eq ''} iss-field__value--empty{/if}">{if $GROUP_VIEW.group eq ''}&mdash;{else}{$GROUP_VIEW.group|escape}{/if}</span>
      </div>
      <div class="iss-field iss-field--full">
        <span class="iss-field__label">{$description.LABEL}</span>
        <span class="iss-field__control iss-field__value{if $GROUP_VIEW.description eq ''} iss-field__value--empty{/if}">{if $GROUP_VIEW.description eq ''}&mdash;{else}{$GROUP_VIEW.description|escape}{/if}</span>
      </div>
    </div>
    {else}
    <div class="iss-card__body iss-formgrid">
      <div class="iss-field">
        <span class="iss-field__label">{$group.LABEL}&nbsp;<span class="iss-required">*</span></span>
        <span class="iss-field__control">{$group.INPUT}</span>
        {if $GROUP_ERRORS.group}<div class="invalid-feedback d-block">{$GROUP_ERRORS.group}</div>{/if}
      </div>
      <div class="iss-field iss-field--full">
        <span class="iss-field__label">{$description.LABEL}&nbsp;<span class="iss-required">*</span></span>
        <span class="iss-field__control">{$description.INPUT}</span>
        {if $GROUP_ERRORS.description}<div class="invalid-feedback d-block">{$GROUP_ERRORS.description}</div>{/if}
      </div>
    </div>
    {/if}
  </section>
  {if $mode eq 'edit'}</div>{/if}
  <input type="hidden" name="id_group" value="{$id_group}">
</form>

{if $mode ne 'view'}
{literal}
<script type="text/javascript">
(function () {
    var form = document.getElementById('groupForm');
    if (!form) return;
    function getField(name) { return form.querySelector('[name="' + name + '"]'); }
    function paint(el, ok) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }
    function clearPaint(el) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
    }
    function fieldValid(name) {
        var el = getField(name);
        if (!el || el.disabled) return true;
        return el.value.trim() !== '';
    }
    // Validación en vivo: verde apenas el campo tiene contenido válido; vacío
    // queda neutro hasta el blur (o hasta enviar el formulario).
    function repaintLive(name) {
        var el = getField(name);
        if (!el || el.disabled) return;
        if (el.value.trim() !== '') paint(el, fieldValid(name));
        else clearPaint(el);
    }

    ['group', 'description'].forEach(function (name) {
        var el = getField(name);
        if (!el) return;
        el.addEventListener('input', function () { repaintLive(name); });
        el.addEventListener('blur', function () {
            var f = getField(name);
            if (f && !f.disabled) paint(f, fieldValid(name));
        });
    });

    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.name === 'cancel') return;
        var ok = true;
        ['group', 'description'].forEach(function (name) {
            var el = getField(name);
            if (!el || el.disabled) return;
            var v = fieldValid(name);
            paint(el, v);
            if (!v) ok = false;
        });
        if (!ok) { e.preventDefault(); return false; }
    });
})();
</script>
{/literal}
{/if}
