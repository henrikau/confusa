{if $is_online === TRUE && $person->isNRENAdmin()} {* Only display if system is placed in CA-mode *}

{* ---------------------------------------------------------------- *
*
*	Change the CA account
*
* ---------------------------------------------------------------- *}

<br />
<br />
<fieldset>
  <legend>{$l10n_legend_caaccount}</legend>
  <br />
  <p class="info">
    {$l10n_infotext_caaccount1}</p>
  <p class="info" style="padding-bottom: 2em">
    {$l10n_infotext_caaccount2}
  </p>
  {* {if $password_label != "undefined"}*}
  <h4>{$l10n_heading_curaccount}</h4>
  <form action="" method="post">
    <div>
      <input type="hidden" name="account" value="edit" />
      {$panticsrf}
    </div>
    <table>
      <tr>
	<td style="width: 20px"></td>
	<td style="width: 150px"></td>
	<td style="width: 200px"></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>{$l10n_label_loginname}</td>
	<td>{$login_name|escape}</td>
	<td><input type="text" name="login_name" value="{$login_name}" /></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>{$l10n_label_password}</td>
	<td><i>{$password}</i></td>
	<td> <input type="password" name="password" value="" /> </td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>
	  <span class="wtf"
		title="{$l10n_title_apname}">
	    {$l10n_label_apname}</span>
	</td>
	<td>{$ap_name}</td>
	<td><input type="text" name="ap_name" value="{$ap_name}" /></td>
	<td></td>
      </tr>

      <tr>
	<td><div class="spacer" ></div></td>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td><input type="reset" value="{$l10n_button_revert}" /></td>
	<td><input type="submit" value="{$l10n_button_update} {$login_name|escape}" /></td>
	<td></td>
	<td></td>
      </tr>
</table>
</form>

<div class="spacer"></div>
</fieldset>

{/if} {* if in CA-mode *}
