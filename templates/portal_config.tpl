<h3>{$l10n_portal_config_title|escape}</h3>

<p class="info">
  {$l10n_portal_config_intro|escape}
</p>

<fieldset>
  <legend>{$l10n_nren_maint_mode_header|escape}</legend>
  <br />
  <p class="info">{$l10n_nren_maint_mode_info|escape}</p>

  {if isset($maint_mode)}
  <p class="info">
    {$maint_mode_msg}
  </p>
  {/if}

  <form method="post" action="">
    {$panticsrf}
    {html_radios name='nren_maint_mode'
	         values=$maint_mode_v
                 output=$maint_mode_t
	         selected=$maint_mode_selected
                 separator='<br />'}

    <br />
    <input type="submit" value="update" />
  </form>
  <br />
</fieldset>

<br />
<fieldset>
  <legend>{$l10n_nren_maint_legend|escape}</legend>
  <br />
  <p class="info">
    {$l10n_nren_maint_msg|escape}
  </p>

  <form method="post" action="">
    {$panticsrf}
    <textarea name="nren_maint_msg"
	      rows="15"
	      cols="70">{if isset($nren_maint_msg)}{$nren_maint_msg|escape}{/if}</textarea>
    <br /><br />
    <input type="submit" value="{$l10n_nren_maint_update_msg|escape}" />
    <br />
  </form>
  <br />
  <hr /><br />
</fieldset>
