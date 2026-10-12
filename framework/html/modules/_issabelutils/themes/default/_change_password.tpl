<script type='text/javascript' src="modules/{$module_name}/themes/default/js/change_password.js"></script>
<section class="iss-card" style="margin:0;">
    <div class="iss-card__body iss-formgrid">
      <div class="iss-field">
        <span class="iss-field__label">{$CURRENT_PASSWORD}</span>
        <span class="iss-field__control"><input type='password' class='form-control' id='curr_pass' name='curr_pass' value='' autocomplete='current-password' /></span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$NEW_PASSWORD}</span>
        <span class="iss-field__control"><input type='password' class='form-control' id='curr_pass_new' name='curr_pass_new' value='' autocomplete='new-password' /></span>
      </div>
      <div class="iss-field">
        <span class="iss-field__label">{$RETYPE_PASSWORD}</span>
        <span class="iss-field__control"><input type='password' class='form-control' id='curr_pass_renew' name='curr_pass_renew' value='' autocomplete='new-password' /></span>
      </div>
      <div class="iss-field iss-field--full">
        <span class="iss-field__control">
          <input type='button' class='button' id='sendChanPass' name='sendChanPss' value='{$CHANGE_PASSWORD_BTN}' />
        </span>
      </div>
    </div>
</section>
<input type="hidden" id="lblCurrentPassAlert" value="{$CURRENT_PASSWORD_ALERT}" />
<input type="hidden" id="lblNewRetypePassAlert"   value="{$NEW_RETYPE_PASSWORD_ALERT}" />
<input type="hidden" id="lblPassNoTMatchAlert" value="{$PASSWORDS_NOT_MATCH}" />
