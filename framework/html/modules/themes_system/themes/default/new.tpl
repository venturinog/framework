<form class="iss-form" method="POST" autocomplete="off">
  <section class="iss-card">
    <header class="iss-card__header"><i class="ti ti-palette"></i> {$title}</header>
    <div class="iss-card__body iss-formgrid">
      <div class="iss-field iss-field--full">
        <span class="iss-field__label">{$themes.LABEL}</span>
        <span class="iss-field__control">{$themes.INPUT}</span>
      </div>
    </div>
  </section>
  <div class="iss-form__toolbar">
    <div class="iss-form__buttons">
      <input class="button" type="submit" name="changeTheme" value="{$CHANGE}">
    </div>
  </div>
</form>
