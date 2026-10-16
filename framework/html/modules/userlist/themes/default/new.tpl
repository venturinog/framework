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
<form id="userForm" class="iss-form" method="POST" autocomplete="off"
      data-is-new="{if $is_new_user eq 'yes'}1{else}0{/if}">
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
  {if $mode eq 'edit'}<div class="iss-reveal">{/if}
  <section class="iss-card">
    <header class="iss-card__header"><i class="ti ti-user-circle"></i> {$LBL_CORE_FIELDS}</header>
    <div class="iss-card__body iss-formgrid">
      <div class="iss-field">
        <span class="iss-field__label">{$name.LABEL}{if $is_new_user eq 'yes'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$name.INPUT}</span>
        {if $USER_ERRORS.name}<div class="invalid-feedback d-block">{$USER_ERRORS.name}</div>{/if}
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$description.LABEL}</span>
        <span class="iss-field__control">{$description.INPUT}</span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$password1.LABEL}{if $is_new_user eq 'yes'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$password1.INPUT}</span>
        {if $USER_ERRORS.password1}<div class="invalid-feedback d-block">{$USER_ERRORS.password1}</div>{/if}
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$password2.LABEL}{if $is_new_user eq 'yes'}&nbsp;<span class="iss-required">*</span>{/if}</span>
        <span class="iss-field__control">{$password2.INPUT}</span>
        {if $USER_ERRORS.password2}<div class="invalid-feedback d-block">{$USER_ERRORS.password2}</div>{/if}
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$group.LABEL}</span>
        <span class="iss-field__control">{$group.INPUT}</span>
      </div>
    </div>
  </section>
  {if $mode eq 'edit'}</div>{/if}

  {$PLUGIN_CONTENT}

  <input type="hidden" name="id_user" value="{$id_user}">
</form>

{literal}
<script type="text/javascript">
(function () {
    var form = document.getElementById('userForm');
    if (!form) return;
    var IS_NEW = form.getAttribute('data-is-new') === '1';

    function getField(name) {
        return form.querySelector('[name="' + name + '"]');
    }
    function paint(el, ok) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(ok ? 'is-valid' : 'is-invalid');
    }
    function clearPaint(el) {
        if (!el) return;
        el.classList.remove('is-valid', 'is-invalid');
    }

    function nameValid() {
        var el = getField('name');
        if (!el || el.disabled) return true;
        return el.value.trim() !== '';
    }
    function p1Valid() {
        var p1 = getField('password1');
        if (!p1 || p1.disabled) return true;
        if (IS_NEW) return p1.value !== '';
        return true; // En edición, vacío = no cambiar la clave.
    }
    function p2Valid() {
        var p1 = getField('password1');
        var p2 = getField('password2');
        if (!p1 || !p2 || p1.disabled || p2.disabled) return true;
        if (IS_NEW) return p1.value !== '' && p2.value !== '' && p1.value === p2.value;
        // En edición, dejar ambas vacías significa "no cambiar la clave".
        if (p1.value === '' && p2.value === '') return true;
        return p2.value !== '' && p2.value === p1.value;
    }
    var validators = { name: nameValid, password1: p1Valid, password2: p2Valid };

    // Validación en vivo: verde cuando el campo ya es válido; el rojo aparece
    // en cuanto el campo tiene contenido inválido (incluido password2 cuando no
    // coincide), y un campo vacío queda neutro hasta el blur.
    function repaintLive(name) {
        var el = getField(name);
        if (!el || el.disabled) return;
        if (el.value !== '') paint(el, validators[name]());
        else clearPaint(el);
    }
    function repaintPasswordsLive() {
        repaintLive('password1');
        repaintLive('password2');
    }

    ['name', 'password1', 'password2'].forEach(function (name) {
        var el = getField(name);
        if (!el) return;
        el.addEventListener('input', function () {
            if (name === 'password1' || name === 'password2') repaintPasswordsLive();
            else repaintLive(name);
        });
        el.addEventListener('blur', function () {
            var f = getField(name);
            if (!f || f.disabled) return;
            paint(f, validators[name]());
        });
    });

    form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.name === 'cancel') return;
        var ok = true;
        ['name', 'password1', 'password2'].forEach(function (name) {
            var el = getField(name);
            if (!el || el.disabled) return;
            var v = validators[name]();
            paint(el, v);
            if (!v) ok = false;
        });
        if (!ok) { e.preventDefault(); return false; }
    });
})();
</script>
{/literal}
{/if}
