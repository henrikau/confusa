<form action="" method="post">
  <div class="spacer"></div>
  {include file="csr/uap.tpl"}
  <div class="spacer"></div>
  <div class="csr">
    <fieldset>
      <legend><i>{$l10n_legend_pastenewcsr}</i></legend>
      <div class="spacer"></div>
      <p class="info">
		{$l10n_infotext_pastenewcsr1}
      </p>
      <div class="spacer"></div>
      {include file="csr/email.tpl"}
      <div class="spacer"></div>
      <p>
	<input type="hidden" name="pastedCSR" value="pastedCSR" />
      </p>
      <table>
	<tr>
	  <td colspan="2">
	    <textarea name="user_csr" rows="20" cols=" 60"></textarea><br />
	  </td>
	</tr>
	<tr>
	  <td><div class="spacer"></div></td>
	  <td></td>
	</tr>
	<tr>
	  <td align="right" style="padding-right: 10px">
	    <input type="reset" class="button" value="{$l10n_button_reset}" />
	  </td>
	  <td align="left">
	    <input type="submit"
		   class="button"
		   value="{$l10n_button_uploadcsr}"
		   onclick="return isBoxChecked(aup_box);" />
	    <br />
	  </td>
	</tr>
      </table>
    </fieldset>
  </div>

</form>
