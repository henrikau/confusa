<hr style="width:90%" />
<br />
<i><b>{$owner|escape|replace:',':', '}</b></i>
<br />
<br />
<form action="" method="post">
  <table>
    <tr>
      <td>
	<input type="hidden" name="revoke_operation" value="revoke_by_cn" />
	<input type="hidden" name="common_name" value="{$owner}" />
        {html_radios	name="reason"
	values="$nren_reasons"
	output="$nren_reasons"
	selected="$selected"
	separator="<br />"}

      </td>
      <td style="width: 50px"></td>
      <td>
	<input type="submit"
	       name="submit"
	       value="{$l10n_button_revokeall|escape}"
	       onclick="return confirm('{$l10n_confirm_revokeall1} {$stats[$owner]} {$l10n_confirm_revokeall2}')" />
      </td>
    </tr>
  </table>
</form>
<br />
<br />
