{if $is_online === TRUE && $person->isNRENAdmin()} {* Only display if system is placed in CA-mode *}
{* ---------------------------------------------------------------- *
 *
 *	Modify current CA-account
 *
 * ---------------------------------------------------------------- *}
<br />
<fieldset>
<legend>Change to another CA-account</legend>
<br />

<p class="info">
This is where you can change the account used for communication with the
online CA system for this NREN ({$nrenName}).
It is this account that will be used for all communication with the CA-API.
</p>
<form action="" method="post">
<table>

<tr>
<td></td>
</tr>

<tr>
<td style="width: 25px"></td>
<td style="width: 200px">
{$accountant->createSelectBox($account_list.account, $account_list.all, 'login_name')}
</td>
<td></td>
<td>
<input type="hidden" name="account" value="change" />
<input type="submit" value="Change account" />
</td>
</tr>
</table>
</form>
<br />
</fieldset>
<br />
<br />

{* ---------------------------------------------------------------- *
 *
 * Change the AP-Name
 *
 * ---------------------------------------------------------------- *}
<fieldset>
<legend>Change the AP name</legend>
<br />
<p class="info">
The AP-name must be
the alliance partner name assigned to your NREN. If you don't know about that
name, please contact TERENA and ask for it. Alternatively, you can find it in your
reseller URLs on the Comodo administrative interface.
</p><br />

<form action="" method="post">
<table>
	<tr>
	<td style="width: 25px"></td>
	<td style="width: 200px">AP-Name:</td>
	<td>
		<input type="hidden" name="account" value="change_ap_name" />
		<input type="ap_name" name="ap_name" value="{$account_list.ap_name}" />
	</td>
	</tr>
	<tr>
	<td style="width: 25px"></td>
	<td style="width: 200px"></td>
	<td><input type="submit" name="submit" value="Change" /></td>
	</tr>

</table>
</form>
<br />
</fieldset>

<br />
<br />

{* ---------------------------------------------------------------- *
 *
 *	Change the CA-account
 *
 * ---------------------------------------------------------------- *}

<fieldset>
<legend>Change password</legend>
<br />
<p class="info">
This is where you change the password for the account. This password
<b>must</b> match the credentials set at the CA-site.
</p>
<p class="info">
You can only change the account currently selected for this NREN. If you
want to change another account, you must first update {$nrenName} to use
that account and then you can change the password.
</p><br />

<form action="" method="post">
<table>

<tr>
<td style="width: 25px"></td>
<td style="width: 200px"></td>
<td></td>
<td></td>
</tr>

<tr>
<td></td>
<td>Account:</td>
<td>
<i><b>{$account_list.account}</b></i>
<input type="hidden" name="account" value="edit" />
<input type="hidden" name="login_name" value="{$account_list.account}" />
</td>
<td></td>
</tr>

<tr>
<td></td>
</tr>

<tr>
<td></td>
<td>Password:</td>
<td><input type="password" name="password" value="" /></td>
</tr>

<tr>
<td></td>
</tr>

<tr>
<td></td>
<td></td>
<td><input type="submit" name="submit" value="Change" /></td>
</tr>
</table>
</form>
<br />
</fieldset>

{* ---------------------------------------------------------------- *
 *
 *	Delete an account from the database
 *
 * ---------------------------------------------------------------- *}

<br />
<br />
<fieldset>
<legend>Delete a CA NREN-account</legend>
<br />
<p class="info">
When an CA-account is no longer needed, it should be removed from the
system all together.
</p>
<p class="info">
<b>Note:</b> it is only possible to delete accounts not used by any NREN
at all. If another NREN uses this account, it cannot be deleted.
</p>
<br />

<form action="" method="post">
<table>

<tr>
<td style="width: 25px"> </td>
<td style="width: 200px"></td>
<td></td>
<td></td>
</tr>

<tr>
<td></td>
<td>Account:</td>
<td>
{$accountant->createSelectBox($account_list.account, $account_list.all, 'login_name')}
</td>
<td></td>
</tr>

<tr>
<td></td>
</tr>

<tr>
<td></td>
<td></td>
<td>
<input type="hidden" name="account" value="delete" />
<input type="submit"
	onclick="return confirm('Delete entry?')"
	name="submit" value="Delete" />

</td>

</tr>
</table>
</form>
<br />
</fieldset>
{* ---------------------------------------------------------------- *
 *
 *	Add a new CA-account for this NREN
 *
 * ---------------------------------------------------------------- *}

<br />
<br />
<fieldset>
<legend>Add a new CA NREN-account</legend>
<br />
<p class="info">
Add a new CA-account to the list of available accounts in the
database. In Confusa's current version, this will enable the account for
<b>all</b> NRENs.
</p>
<br />

<form action="" method="post">
<table>

<tr>
<td style="width: 25px"></td>
<td style="width: 200px"></td>
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
</tr>

<tr>
<td></td>
<td><span class="wtf"
	title="The AP-name is used by the remote-CA for identifying the reseller. You should have received this from TERENA.">
	AP-Name:</a></td>
<td><input type="text" name="ap_name" value="" /></td>
</tr>

<tr>
<td></td>
</tr>

<tr>
<td></td>
<td></td>
<td>
<input type="hidden" name="account" value="add" />
<input type="submit" name="submit" value="Create New" />
</td>
</tr>
</table>
</form>
<br />
</fieldset>

{/if} {* if in CA-mode *}
