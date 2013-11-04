<fieldset>
  <legend>{$l10n_legend_contsubsr|escape}</legend>
  <br />
  <p class="info">
    {$l10n_infotext_subscrcont1|escape}
  </p>

  <form method="post" action="">
  <p>
    <input type="hidden" name="setting" value="subscriber_contact" />
    {$panticsrf}
  </p>
    <table>
      <tr>
	<td></td>
	<td style="width: 25px;"><div class="spacer"></div></td>
	<td></td>
      </tr>
      <tr>
	<td align="right">{$l10n_label_contactemail|escape}</td>
	<td></td>
	<td><input type="text" name="contact_email" value="{$subscriberInfo.subscr_email}" /></td>
      </tr>
      <tr>
	<td align="right">{$l10n_label_contactphone|escape}</td>
	<td></td>
	<td><input type="text" name="contact_phone" value="{$subscriberInfo.subscr_phone}" /></td>
      </tr>
      <tr>
	<td align="right">{$l10n_label_respname|escape}</td>
	<td></td>
	<td><input type="text" name="resp_name" value="{$subscriberInfo.subscr_resp_name}" /></td>
      </tr>

      <tr>
	<td align="right">{$l10n_label_respemail|escape}</td>
	<td></td>
	<td><input type="text" name="resp_email" value="{$subscriberInfo.subscr_resp_email}" /></td>
      </tr>

	  <tr>
	<td align="right">{$l10n_label_helpdeskurl|escape}</td>
	<td></td>
	<td><input type="text" name="helpdesk_url" value="{$subscriberInfo.subscr_help_url}" /></td>
	</tr>

	<tr>
	<td align="right">{$l10n_label_helpemail|escape}</td>
	<td></td>
	<td><input type="text" name="helpdesk_email" value="{$subscriberInfo.subscr_help_email}" /></td>
	</tr>

	<tr>
    <td align="right">{$l10n_label_deflang|escape}</td>
    <td></td>
    <td>{html_options name="language" selected=$current_language output=$languages values=$language_codes}</td>
  </tr>

      <tr>
	<td align="right">
	  <input type="reset" value="{$l10n_button_reset|escape}" />
	</td>
	<td></td>
	<td>
	  <input type="submit" value="{$l10n_button_update|escape}" />
	</td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
