{if $is_online === TRUE && $person->isNRENAdmin()} {* Only display if system is placed in CA-mode *}

{* ---------------------------------------------------------------- *
*
*	Change the CA account
*
* ---------------------------------------------------------------- *}

<br />
<br />
<fieldset>
  <legend>Change CA NREN-account</legend>
  <br />
  <p class="info">
    Change/enter the account with which your NREN connects to the Comodo-API.
    You should have received account information either from TERENA or any other
    contract partner via which you signed up to Confusa.</p>
  <p class="info" style="padding-bottom: 2em">
    Note: The account password will be encrypted using 256-Bit AES in CFB blockmode
    before being stored in the DB.
  </p>
  {* {if $password_label != "undefined"}*}
  <h4>Current account</h4>
  <form action="" method="post">
    <input type="hidden" name="account" value="edit" />
    <table>
      <tr>
	<td style="width: 20px"></td>
	<td style="width: 150px"></td>
	<td style="width: 200px"></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>Login-name:</td>
	<td>{$login_name|escape}</td>
	{if $login_name === 'undefined'}
	<td><input disabled="disabled" type="text" name="login_name" value="{$login_name}" /></td>
	{else}
	<td><input type="text" name="login_name" value="{$login_name}" /></td>
	{/if}
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>Password:</td>
	<td>{$password_label}</td>
	{if $login_name === 'undefined'}
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
		title="The AP-name is used by the remote-CA for identifying the reseller. You should have received this from TERENA.">
	    AP-Name:</span>
	</td>
	<td>{$ap_name}</td>
	{if $ap_name === 'undefined'}
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
	{if $login_name === 'undefined'}
	<td><input disabled="disabled" type="reset" value="Revert to default" /></td>
	<td><input disabled="disabled" type="submit" value="Update {$login_name|escape}" /></td>
	{else}
	<td><input type="reset" value="Revert to default" /></td>
	<td><input type="submit" value="Update {$login_name|escape}" /></td>
	{/if}
	<td></td>
	<td></td>
      </tr>
</table
</form>

<div class="spacer"></div>

<h4>Add new </h4>
  <form action="" method="post">
    <input type="hidden" name="account" value="new" />
    <table>
      <tr>
	<td style="width: 20px"></td>
	<td style="width: 150px"></td>
	<td style="width: 200px"></td>
	<td></td>
      </tr>
      <tr>
	<td></td>
	<td>Login-name:</td>
	<td><input type="text" name="login_name" value="" /></td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>Password:</td>
	<td> <input type="password" name="password" value="" /> </td>
	<td></td>
      </tr>

      <tr>
	<td></td>
	<td>
	  <span class="wtf"
		title="The AP-name is used by the remote-CA for identifying the reseller. You should have received this from TERENA.">
	    AP-Name:</span>
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
	<td><input type="reset" value="Clear" /></td>
	<td><input type="submit" value="Add account" /></td>
	<td></td>
      </tr>
</table
</form>

{/if} {* if in CA-mode *}
