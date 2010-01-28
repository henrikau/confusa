<fieldset>
<legend>{$l10n_legend_updatenren|escape}</legend>
<br />
<p class="info">
{$l10n_infotext_nrencont1|escape}
</p>

<form method="post" action="">
  <p>
    <input type="hidden" name="setting" value="nren_contact" />
  </p>
  <table>
    <tr>
      <td></td>
      <td style="width: 25px"><div class="spacer"></div></td>
      <td></td>
    </tr>

  <tr>
    <td align="right">{$l10n_label_contactemail}</td>
    <td></td>
	{if isset($nrenInfo.contact_email)}
		<td><input type="text" name="contact_email" value="{$nrenInfo.contact_email}" /></td>
	{else}
		<td><input type="text" name="contact_email" value="" /></td>
	{/if}
  </tr>
    <tr>
      <td align="right">{$l10n_label_nrenphone|escape}</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.contact_phone)}
	<input type="text" name="contact_phone"
	value="{$nrenInfo.contact_phone}" />
	  {else}
		<input type="text" name="contact_phone" value="" />
	  {/if}
      </td>
    </tr>
    <tr>
      <td align="right">{$l10n_label_certmail|escape}</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.cert_email)}
	<input type="text" name="cert_email"
	value="{$nrenInfo.cert_email}" />
	  {else}
	<input type="text" name="cert_email"
	value="" />
	  {/if}
      </td>
    </tr>
    <tr>
      <td align="right">{$l10n_label_certphone|escape}</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.cert_phone)}
	<input type="text" name="cert_phone"
	value="{$nrenInfo.cert_phone}" />
	  {else}
	<input type="text" name="cert_phone"
	value="" />
	  {/if}
      </td>
    </tr>
    <tr>
      <td align="right">{$l10n_label_nrenurl|escape}</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.url)}
	<input type="text" name="url" value="{$nrenInfo.url}"/>
	  {else}
	<input type="text" name="url" value="" />
	  {/if}
      </td>
    </tr>
    
  <tr>
    <td align="right">{$l10n_label_deflang|escape}</td>
    <td></td>
    <td>{html_options name="language" selected=$current_language
    output=$languages values=$language_codes}</td>
  </tr>

    <tr>
      <td></td>
      <td></td>
      <td>
	<div class="spacer"></div>
      </td>
    </tr>

{if $personal == TRUE}
	<tr>
		<td align="right">{$l10n_label_certvalidity}</td>
		<td></td>
		<td>
		{html_radios
		 name		= "cert_validity"
		 options	=  $validity_options
		 selected	=  $nren->getCertValidity()
		 separator	=  "<br />"}
		 </td>
		</tr>
			 <tr><td style="padding-top: 1em" colspan="3">
		  <span style="font-size: 0.8em; font-style: italic">
			{$l10n_infotext_certvalidity}
		  </span>
		  </td>
		  </tr>
		<tr>
		  <td></td>
		  <td></td>
		  <td style="margin-bottom: 2em">&nbsp;
		  </td>
		</tr>
{/if}

    <tr>
      <td align="right">{$l10n_label_encertmail}</td>
      <td></td>
      <td>
	{html_radios
	name      = "enable_email"
	options   = $enable_options
	selected  = $nren->getEnableEmail()
	separator = "<br />"}
      </td>
    </tr>
      <tr><td style="padding-top: 1em" colspan="3">
      <span style="font-size: 0.8em; font-style: italic">
		{$l10n_infotext_encertmail}
      </span>
      </td>
      </tr>
    <tr>
      <td></td>
      <td></td>
      <td>
	<div class="spacer"></div>
      </td>
    </tr>

  <tr>
    <td align="right"><input type="reset" value="{$l10n_button_reset|escape}" /></td>
    <td></td>
    <td><input type="submit" value="{$l10n_button_update|escape}" /></td>
    </tr>
  </table>
</form>
<br />
</fieldset>
