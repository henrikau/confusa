
{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN administration</h3>
<br />


{* ---------------------------------------------------------------- *
 *
 *	List and modify subscribers to NREN
 *
 * ---------------------------------------------------------------- *}

<fieldset>
<legend>Subscriber accounts for: {$nrenName}</legend>
<br />
<p class="info">
   Add or change subscriber accounts. A subscriber is an organization
   belonging to the current NREN ({$nrenName}). This is where the status
   of these subscribers can be changed, new added or existing deleted.
</p>
<br />
<p class="info">
	Note: The ID is assigned automatically by Confusa and is the unique identifier
	of the organization.
</p>
<br />
<table>
<tr>
<td style="width: 25px"></td>
<td style="width: 70px"><b>ID</b></td>
<td style="width: 200px"><b>Name</b></td>
<td><b>State</b></td>
<td></td>
</tr>
</table>

{foreach from=$subscriber_list item=row}
		<table>
		<tr>
			{assign value=$nren->format_subscr_on_state($row.org_state) var=style}

			{* Show the delete-subscriber button *}
			<td style="width: 25px">{$nren->delete_button('subscriber', $row.subscriber, $row.subscriber_id)}</td>
			<td style="width: 70px; {$style}">
				{$row.subscriber_id}
			</td>
			<td style="width: 200px; {$style}">
				{$row.subscriber}

				{if $row.subscriber == $self_subscriber}
						<span title="Your own institution" style="cursor:help">(*)</span>
				{/if}
			</td>
			<td>
				<form action="" method="post">
					<div>
					<input type="hidden" name="subscriber" value="edit" />
					<input type="hidden" name="id" value="{$row.subscriber_id}" />
					{$nren->createSelectBox($row.org_state,	null, state)}
					<input type="submit" class="button" value="Update" />
					</div>
				</form>
			</td>
		</tr>
		</table>
{/foreach}

<br />
<form action="" method="post">
<table>
{* Field for adding new subscribers *}
<tr>
	<td style="width: 25px"></td>
	<td style="width: 70px"></td>
	<td style="width: 200px"></td>
	<td></td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td style="witdh: 25px"></td>
	<td style="width: 50px"><input type="hidden" name="subscriber" value="add" /></td>
	<td><input type="text" name="name" /></td>
	<td>{$nren->createSelectBox('', null, 'state')}</td>
	<td> {* air *} </td>
	<td>
		<input type="submit" value="Add new" />
	</td>

</tr>
</table>
</form>
<br />
</fieldset>
<br />

{if $caMode == 1} {* Only display if system is placed in CA-mode *}
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
{$nren->createSelectBox($account_list.account, $account_list.all, 'login_name')}
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
{$nren->createSelectBox($account_list.account, $account_list.all, 'login_name')}
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
{/if} {* if user is admin *}

<br />
<br />
