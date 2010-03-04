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
    <p>
      <input type="hidden" name="account" value="edit" />
    </p>
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
	{if $login_name === $l10n_fieldval_undefined}
	<td><input disabled="disabled" type="text" name="login_name" value="{$login_name}" /></td>
	{else}
	<td><input type="text" name="login_name" value="{$login_name}" /></td>
	{/if}
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>{$l10n_label_password}</td>
	<td><i>{$l10n_label_passwhidden}</i></td>
	{if $login_name === $l10n_fieldval_undefined}
	<td> <input disabled="disabled" type="password" name="password" value="" /> </td>
	{else}
	<td> <input type="password" name="password" value="" /> </td>
	{/if}
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
	{if $ap_name === $l10n_fieldval_undefined}
	<td><input type="text" disabled="disabled" name="ap_name" value="{$ap_name}" /></td>
	{else}
	<td><input type="text" name="ap_name" value="{$ap_name}" /></td>
	{/if}
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
	{if $login_name === $l10n_fieldval_undefined}
	<td><input disabled="disabled" type="reset" value="{$l10n_button_revert}" /></td>
	<td><input disabled="disabled" type="submit" value="{$l10n_button_update} {$login_name|escape}" /></td>
	{else}
	<td><input type="reset" value="{$l10n_button_revert}" /></td>
	<td><input type="submit" value="{$l10n_button_update} {$login_name|escape}" /></td>
	{/if}
	<td></td>
	<td></td>
      </tr>
</table>
</form>

<div class="spacer"></div>

<h4>{$l10n_heading_addnew}</h4>
  <form action="" method="post">
    <p>
      <input type="hidden" name="account" value="new" />
    </p>
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
	<td><input type="text" name="login_name" value="" /></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>{$l10n_label_password}</td>
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
	<td><input type="text" name="ap_name" value="" /></td>
	<td></td>
      </tr>

      <tr>
	<td><div class="spacer" ></div></td>
	<td></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td><input type="reset" value="{$l10n_button_clear}" /></td>
	<td><input type="submit" value="{$l10n_button_addnew}" /></td>
	<td></td>
      </tr>
</table>
</form>
</fieldset>

{/if} {* if in CA-mode *}
