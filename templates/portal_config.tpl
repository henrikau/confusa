<h4>{$l10n_portal_config_title}</h4>

<fieldset>
  <legend>{$l10n_nren_maint_legend}</legend>
  <p class="info">{$l10n_nren_maint_msg}</p>
  <hr /><br />
  <p class="info">{$l10n_nren_maint_toggle}</p>

  <form method="post" action="">
    {$panticsrf}
    <textarea name="nren_maint_msg"
	      rows="15"
	      cols="70">{if isset($nren_maint_msg)}{$nren_maint_msg|escape}{/if}</textarea>
    <br /><br />
    <input type="submit" value="{$l10n_nren_maint_update_msg}" />
  </form>
  <br />
  <hr /><br />
</fieldset>
