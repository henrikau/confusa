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
contract partner via which you signed up to Confusa.</p><br />

<p class="info">
Note: The account password will be encrypted using 256-Bit AES in CFB blockmode
before being stored in the DB.
</p>
<br />

<table>
<tr>
<td style="width: 20px"></td>
<td style="width: 200px">
	<b>Current account</b>
</td>
<td></td>
</tr>
<tr>
<td></td>
<td>
	Login-name:
</td>
<td>
	{$login_name}
</td>
</tr>
<tr>
<td></td>
<td>
	Password:
</td>
<td>
	{$password_label}
</td>
</tr>
<tr>
<td></td>
<td>
	AP-Name:
</td>
<td>
	{$ap_name}
</td>
</tr>
</table

<div class="spacer"></div>

<form action="" method="post">
<table>

<tr>
<td style="width: 20px"></td>
<td style="width: 200px"><b>Change account</b></td>
<td></td>
<td></td>
</tr>

<tr>
<td></td>
<td>Name:</td>
<td><input type="text" name="login_name" value="" /></td>
<td></td>
</tr>

<tr>
<td></td>
</tr>

<tr>
<td></td>
<td>Password:</td>
<td><input type="password" name="password" value="" /></td>
<td>
</td>
</tr>

<tr>
<td></td>
<td><span class="wtf"
	title="The AP-name is used by the remote-CA for identifying the reseller. You should have received this from TERENA.">
	AP-Name:</span></td>
<td><input type="text" name="ap_name" value="" /></td>
</tr>

<tr>
<td></td>
</tr>

<tr>
<td></td>
<td></td>
<td>
<input type="hidden" name="account" value="change" />
<input type="submit" name="submit" value="Change" />
</td>
</tr>
</table>
</form>
<br />
</div>
</fieldset>

{/if} {* if in CA-mode *}
