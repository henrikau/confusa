<fieldset>
  <legend>{$l10n_legend_nren_settings|escape}</legend>
  <br />
  <p class="info">
    {$l10n_infotext_nrencont1|escape}
  </p>

  <form method="post" action="">
    <p>
      <input type="hidden" name="setting" value="nren_contact" />
      {$panticsrf}
    </p>
    <table summary="{$l10n_infotext_nren_settings_summary}">
      <tr>
	<td></td>
	<td style="width: 25px"><div class="spacer"></div></td>
	<td></td>
      </tr>


      {* NREN contact email *}
      <tr>
	<td align="right" valign="top">
	  {$l10n_label_contactemail}
	</td>
	<td></td>
	<td>
	  {if isset($nrenInfo.contact_email)}
	  <input type="text" name="contact_email" value="{$nrenInfo.contact_email}" />
	  {else}
	  <input type="text" name="contact_email" value="" />
	  {/if}
	  <br />
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_nren_contact_email}
	  </span>
	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>

      {* Contact phone for the NREN *}
      <tr>
	<td align="right" valign="top">{$l10n_label_nrenphone|escape}</td>
	<td></td>
	<td>
	  <input type="text"
		 name="contact_phone"
		 {if isset($nrenInfo.contact_phone)}
		 value="{$nrenInfo.contact_phone}"
		 {else}
		 value=""
		 {/if}
		 />
	  <br />
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_nren_contact_phone}
	  </span>
	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>

      {* CERT email *}
      <tr>
	<td align="right" valign="top">{$l10n_label_certmail|escape}</td>
	<td></td>
	<td>
	  <input type="text"
		 name="cert_email"
		 {if isset($nrenInfo.cert_email)}
		 value="{$nrenInfo.cert_email}"
		 {else}
		 value=""
		 {/if}
		 />
	  <br />
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_nren_cert_email}
	  </span>
	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>

      {* CERT phone *}
      <tr>
	<td align="right" valign="top">{$l10n_label_certphone|escape}</td>
	<td></td>
	<td>
	  <input type="text"
		 name="cert_phone"
		 {if isset($nrenInfo.cert_phone)}
		 value="{$nrenInfo.cert_phone}"
		 {else}
		 value=""
		 {/if}
		 />
	  <br />
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_nren_cert_phone}
	  </span>
	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>

      {* NREN portal URL *}
      <tr>
	<td align="right" valign="top">{$l10n_label_nrenurl|escape}</td>
	<td></td>
	<td>
	  {if isset($nrenInfo.url)}
	  <input type="text" name="url" value="{$nrenInfo.url}"/>
	  {else}
	  <input type="text" name="url" value="" />
	  {/if}
	  <br />
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_nren_portal_url}
	  </span>
	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>

	  {* WAYF URL *}
	  <tr>
		<td align="right" valign="top">{$l10n_label_wayfurl}</td>
		<td></td>
		<td>
			{if isset($nrenInfo.wayf_url)}
				<input type="text" name="wayf_url" value="{$nrenInfo.wayf_url}" />
			{else}
				<input type="text" name="wayf_url" value="" />
			{/if}
			<br />
			<span style="font-size: 0.8em; font-style: italic">
				{$l10n_infotext_wayf_url}
			</span>
		</td>
	  </tr>
	  <tr><td colspan="3"><div class="spacer"></div></td></tr>

      {* Default language for the NREN *}
      <tr>
	<td align="right" valign="top">{$l10n_label_deflang|escape}</td>
	<td></td>
	<td>{html_options name="language" selected=$current_language output=$languages values=$language_codes}
	  <br />
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_nren_portal_language}
	  </span>

	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>

      {if $personal == TRUE}
      <tr>
	<td align="right" valign="top">{$l10n_label_certvalidity}</td>
	<td></td>
	<td>
        {html_radios name='cert_validity' options=$validity_options selected=$nren->getCertValidity separator='<br />'}
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
      <tr><td colspan="3"><div class="spacer"></div></td></tr>
      {/if}

      {* The number of valid email-addresses to allow in the
      SAN *}
      <tr>
	<td align="right" valign="top">{$l10n_label_encertmail|escape}</td>
	<td></td>
	<td>
	  {html_radios name = 'enable_email' options=$enable_options selected=$nren->getEnableEmail() separator = '<br />'}
	  <span style="font-size: 0.8em; font-style: italic">
	    {$l10n_infotext_encertmail}
	  </span>
	</td>
      </tr>
      <tr><td colspan="3"><div class="spacer"></div></td></tr>
	<tr>
		<td align="right">
			{$l10n_label_reauthtimeout}
		</td>
		<td></td>
		<td>
			{if isset($nrenInfo.reauth_timeout)}
				<input style="width: 3em; text-align: right" type="text" name="reauth_timeout" value="{$nrenInfo.reauth_timeout}" />
			{else}
				<input style="width: 3em" type="text" name="reauth_timeout" value="" />
			{/if}
		</td>
	<tr>
		<td></td>
		<td></td>
		<td><span style="font-size: 0.8em; font-style: italic">{$l10n_infotext_reauthtimeout}</span></td>
	</tr>
	<tr><td colspan="3"><div class="spacer"></div></td></tr>
      <tr>
	<td align="right">
	  <input type="reset"
		 value="{$l10n_button_reset|escape}" />
	</td>
	<td></td>
	<td>
	  <input type="submit"
		 value="{$l10n_button_update|escape}" />
	</td>
      </tr>

    </table>
  </form>
  <br />
</fieldset>
