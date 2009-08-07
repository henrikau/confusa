
{if $person->inAdminMode() && $person->isNRENAdmin()}
<H3>NREN administration</H3>
<BR />


{* ---------------------------------------------------------------- *
 *
 *	List and modify subscribers to NREN
 *
 * ---------------------------------------------------------------- *}

<fieldset>
<legend>Subscriber accounts for: {$nrenName}</legend>
<BR />
<P class="info">
   Add or change subscriber accounts. A subscriber is an organization
   belonging to the current NREN ({$nrenName}). This is where the status
   of these subscribers can be changed, new added or existing deleted.
</P>
<BR />
<table>
<tr>
<td style="width: 25px"></td>
<td style="width: 200px"><B>Name</B></td>
<td><B>State</B></td>
<td></td>
</tr>
</table>

{foreach from=$subscriber_list item=row}
		<table>
		<tr>
			{* Show the delete-subscriber button *}
			<td style="width: 25px">{$nren->delete_button('subscriber', $row.subscriber)}</td>
			<td style="width: 200px">{$nren->format_subscr_on_state($row.subscriber, $row.org_state)}</td>
			<td>
				<form action="" method="POST">
					<INPUT TYPE="hidden" NAME="subscriber" VALUE="edit" />
					<INPUT TYPE="hidden" NAME="name" VALUE="{$row.subscriber}" />
					{$nren->createSelectBox($row.org_state,	null, state)}
					<INPUT TYPE="submit" CLASS="button" VALUE="Update" />
				</form>
			</td>
		</tr>
		</table>
{/foreach}

<BR />
<form action="" method="POST">
<table>
{* Field for adding new subscribers *}
<tr>
	<td style="width: 25px"></td>
	<td style="width: 200px"></td>
	<td></td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td><INPUT TYPE="hidden" NAME="subscriber" VALUE="add" /></td>
	<td><INPUT TYPE="TEXT" NAME="name" /></td>
	<td>{$nren->createSelectBox('', null, 'state')}</td>
	<td> {* air *} </td>
	<td>
		<INPUT TYPE="submit" VALUE="Add new" />
	</td>

</tr>
</table>
</form>
<BR />
</fieldset>
<BR />

{if caMode == 1} {* Only display if system is placed in CA-mode *}
{* ---------------------------------------------------------------- *
 *
 *	Modify current CA-account
 *
 * ---------------------------------------------------------------- *}
<BR />
<FIELDSET>
<LEGEND>Change to another CA-account</LEGEND>
<BR />

<P CLASS="info">
This is where you can change the account used for communication with the
online CA system for this NREN ({$nrenName}).
It is this account that will be used for all communication with the CA-API.
</P>
<FoRM ACTION="" METHOD="POST">
<table>
<INPUT TYPE="hidden" NAME="account" VALUE="change">

<tr>
</tr>

<tr>
<td style="width: 25px"></td>
<td style="width: 200px">
{$nren->createSelectBox($account_list.account, $account_list.all, 'login_name')}
</td>
<td></td>
<td>
<INPUT TYPE="submit" VALUE="Change account">
</td>
</tr>
<BR />
</table>
</FORM>
<BR />
</FIELDSET>
<BR />
<BR />

{* ---------------------------------------------------------------- *
 *
 *	Change the CA-account
 *
 * ---------------------------------------------------------------- *}

<FIELDSET>
<LEGEND>Change password</LEGEND>
<BR />
<P class="info">
This is where you change the password for the account. This password
<B>must</B> match the credentials set at the CA-site.
</P>
<P class="info">
You can only change the account currently selected for this NREN. If you
want to change another account, you must first update {$nrenName} to use
that account and then you can change the password.
</P><BR />

<table>
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="edit">

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
<I><B>{$account_list.account}</B></I>
<INPUT TYPE="hidden" NAME="login_name" VALUE="{$account_list.account}">
</td>
<td></td>
</tr>

<tr>
</tr>

<tr>
<td></td>
<td>Password:</td>
<td><INPUT TYPE="password" NAME="password" VALUE=""></td>
</tr>

<tr>
</tr>

<tr>
<td></td>
<td></td>
<td><INPUT TYPE="submit" NAME="submit" VALUE="Change"></td>
</tr>
</FORM>
</table>
<BR />
</FIELDSET>

{* ---------------------------------------------------------------- *
 *
 *	Delete an account from the database
 *
 * ---------------------------------------------------------------- *}

<BR />
<BR />
<FIELDSET>
<LEGEND>Delete a CA NREN-account</LEGEND>
<BR />
<P CLASS="info">
When an CA-account is no longer needed, it should be removed from the
system all together.
</P>
<P CLASS="info">
<B>Note:</B> it is only possible to delete accounts not used by any NREN
at all. If another NREN uses this account, it cannot be deleted.
</P>
<BR />

<table>
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="delete">

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
</tr>

<tr>
<td></td>
<td></td>
<td>
<INPUT TYPE="submit"
	onClick="return confirm('Delete entry?')"
	NAME="submit" VALUE="Delete">

</td>

</tr>
</FORM>
</table>
<BR />
</FIELDSET>
{* ---------------------------------------------------------------- *
 *
 *	Add a new CA-account for this NREN
 *
 * ---------------------------------------------------------------- *}

<BR />
<BR />
<FIELDSET>
<LEGEND>Add a new CA NREN-account</LEGEND>
<BR />
<P CLASS="info">
Add a new CA-account to the list of available accounts in the
database. In Confusa's current version, this will enable the account for
<B>all</B> NRENs.
</P>
<BR />

<table>
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="add">

<tr>
<td style="width: 25px"></td>
<td style="width: 200px"></td>
<td></td>
<td></td>
</tr>

<tr>
<td></td>
<td>Name:</td>
<td><INPUT TYPE="text" NAME="login_name" VALUE=""></td>
<td></td>
</tr>

<tr>
</tr>

<tr>
<td></td>
<td>Password:</td>
<td><INPUT TYPE="password" NAME="password" VALUE=""></td>
</tr>

<tr>
</tr>

<tr>
<td></td>
<td></td>
<td><INPUT TYPE="submit" NAME="submit" VALUE="Create New"></td>
</tr>
</FORM>
</table>
<BR />
</FIELDSET>

{/if} {* if in CA-mode *}
{/if} {* if user is admin *}

<BR />
<BR />
