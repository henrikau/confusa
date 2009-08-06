
{if $person->inAdminMode() && $person->is_nren_admin()}
<H3>NREN administration</H3>
<BR />


{* ---------------------------------------------------------------- *
 *
 *	List and modify subscribers to NREN
 *
 * ---------------------------------------------------------------- *}

<H4>Subscriber accounts administration</H4>

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
		<tr></tr>
		</table>
{/foreach}

<form action="" method="POST">
<table>
{* Field for adding new subscribers *}
<tr>
<div class="spacer"></div>
</tr>
<tr>
	<td style="width: 25px">
		<INPUT TYPE="hidden" NAME="subscriber" VALUE="add" />
	</td>
	<td style="width: 200px"><INPUT TYPE="TEXT" NAME="name" /></td>
	<td>{$nren->createSelectBox('', null, 'state')}</td>
	<td> {* air *} </td>
	<td>
		<INPUT TYPE="submit" VALUE="Add new" />
	</td>

</tr>
</table>
</form>

{* ---------------------------------------------------------------- *
 *
 *	Modify current CA-account
 *
 * ---------------------------------------------------------------- *}
<BR />
<H4>Change to another CA NREN-account</H4>
<FoRM ACTION="" METHOD="POST">
<table>
<INPUT TYPE="hidden" NAME="account" VALUE="change">

<tr>
</tr>

<tr>
<td>
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

{* ---------------------------------------------------------------- *
 *
 *	Change the CA-account
 *
 * ---------------------------------------------------------------- *}
<BR />
<H4>Change the current CA NREN-account</H4>

<table>
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="edit">

<tr>
</tr>

<tr>
<td style="width: 100px">Account:</td>
<td></td>
<td>
<I><B>{$account_list.account}</B></I>
<INPUT TYPE="hidden" NAME="login_name" VALUE="{$account_list.account}">
</td>
<td></td>
</tr>

<tr>
</tr>

<tr>
<td style="width: 100px">Password:</td>
<td></td>
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

{* ---------------------------------------------------------------- *
 *
 *	Delete an account from the database
 *
 * ---------------------------------------------------------------- *}
<BR />
<H4>Delete a CA NREN-account</H4>
<table>
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="delete">

<tr>
</tr>

<tr>
<td style="width: 100px">Account:</td>
<td></td>
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

{* ---------------------------------------------------------------- *
 *
 *	Add a new CA-account for this NREN
 *
 * ---------------------------------------------------------------- *}

<BR />
<H4>Add a new CA NREN-account</H4>

<table>
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="add">

<tr>
</tr>

<tr>
<td style="width: 100px">Name:</td>
<td></td>
<td><INPUT TYPE="text" NAME="login_name" VALUE=""></td>
<td></td>
</tr>

<tr>
</tr>

<tr>
<td style="width: 100px">Password:</td>
<td></td>
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


{/if}
