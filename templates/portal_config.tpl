<h4>{$l10n_portal_config_title}</h4>

<fieldset>
  <legend>{$l10n_nren_maint_mode_header}</legend>
  <br />
  <p class="info">{$l10n_nren_maint_mode_info}</p>

  {if isset($maint_mode)}
  <p class="info">
  {if $maint_mode}
  {$l10n_nren_maint_mode_enabled}
  {else}
  {$l10n_nren_maint_mode_disabled}
  {/if}
  </p>
  {/if}

  <form method="post" action="">
    {$panticsrf}
    <label name="maint_mode">Enabled </label>
    <input type="radio"
	   name="nren_maint_mode"
	   value="y"
	   {if $maint_mode}checked="checked"{/if}
	   />
    <label name="maint_mode">Disabled </label>
    <input type="radio"
	   name="nren_maint_mode"
	   value="n"
	   {if ! $maint_mode}checked="checked"{/if}
	   />
    <input type="submit" value="update" />
  </form>
  <br />
</fieldset>

<br />
<fieldset>
  <legend>{$l10n_nren_maint_legend}</legend>
  <br />
  <p class="info">
    {$l10n_nren_maint_msg}
  </p>
  <hr /><br />
  <p class="info">{$l10n_nren_maint_toggle}</p>

  <form method="post" action="">
    {$panticsrf}
    <textarea name="nren_maint_msg"
	      rows="15"
	      cols="70">{if isset($nren_maint_msg)}{$nren_maint_msg|escape}{/if}</textarea>
    <br /><br />
    <input type="submit" value="{$l10n_nren_maint_update_msg}" />
    <br />
  </form>
  <br />
  <hr /><br />
</fieldset>
