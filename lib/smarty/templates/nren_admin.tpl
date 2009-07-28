
{if $person->in_admin_mode() && $person->is_nren_admin()}
<H3>NREN administration</H3>
<BR />
{assign var='table'	value='<div class="admin_table">'}
{assign var='table_e'	value='</div>'}
{assign var='tr'	value='<DIV CLASS="admin_table_row">'}
{assign var='tr_e'	value='</DIV>'}
{assign var='td'	value='<DIV CLASS="admin_table_cell">'}
{assign var='td_e'	value='</DIV>'}


{* ---------------------------------------------------------------- *
 *
 *	List and modify subscribers to NREN
 *
 * ---------------------------------------------------------------- *}

<H4>Subscriber accounts administration</H4>
{$table}
{$tr}
{$td}{$td_e}
{$td}<B>Name</B>{$td_e}
{$td}<B>State</B>{$td_e}
{$td}{$td_e}
{$tr_e}
{section name=sl_loop loop=$subscriber_list}
	{assign var='row' value=$subscriber_list[sl_loop]}
		{$tr}
			{* Show the delete-subscriber button *}
			{$td}{$nren->delete_button('subscriber', $row.subscriber)}{$td_e}
			{$td}{$nren->format_subscr_on_state($row.subscriber, $row.org_state)}{$td_e}
			{$td}
				<FORM ACTION="">
				<INPUT TYPE="hidden" NAME="subscriber" VALUE="edit">
				<INPUT TYPE="hidden" NAME="name" VALUE="{$row.subscriber}">
				{$nren->createSelectBox($row.org_state,	null, state)}
			{$td_e}
			{$td}

			{$td_e}
			{$td}
				<INPUT TYPE="submit" CLASS="button" VALUE="Update" />
				</FORM>
			{$td_e}
		{$tr_e}
		{$tr}{$tr_e}
{/section}

{* Field for adding new subscribers *}
{$tr}
<div class="spacer"></div>
{$tr_e}
{$tr}
	{$td}
		<FORM ACTION="" METHOD="GET">
		<INPUT TYPE="hidden" NAME="subscriber" VALUE="add" />
	{$td_e}
	{$td}<INPUT TYPE="TEXT" NAME="name" />{$td_e}
	{$td}{$nren->createSelectBox('', null, 'state')}{$td_e}
	{$td} {* air *} {$td_e}
	{$td}
		<INPUT TYPE="submit" VALUE="Add new" />
		</FORM>
	{$td_e}

{$tr_e}
{$table_e}

{* ---------------------------------------------------------------- *
 *
 *	Modify current CA-account
 *
 * ---------------------------------------------------------------- *}
<BR />
<H4>Change to another CA NREN-account</H4>
{$table}
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="change">

{$tr}
{$tr_e}

{$tr}
{$td}
{$nren->createSelectBox($account_list.account, $account_list.all, 'login_name')}
{$td_e}
{$td}{$td_e}
{$td}
<INPUT TYPE="submit" VALUE="Change account">
{$td_e}
{$tr_e}
</FORM>
<BR />
{$table_e}

{* ---------------------------------------------------------------- *
 *
 *	Change the CA-account
 *
 * ---------------------------------------------------------------- *}
<BR />
<H4>Change the current CA NREN-account</H4>

{$table}
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="edit">

{$tr}
{$tr_e}

{$tr}
{$td}Account:{$td_e}
{$td}{$td_e}
{$td}
<I><B>{$account_list.account}</B></I>
<INPUT TYPE="hidden" NAME="login_name" VALUE="{$account_list.account}">
{$td_e}
{$td}{$td_e}
{$tr_e}

{$tr}
{$tr_e}

{$tr}
{$td}Password:{$td_e}
{$td}{$td_e}
{$td}<INPUT TYPE="password" NAME="password" VALUE="">{$td_e}
{$tr_e}

{$tr}
{$tr_e}

{$tr}
{$td}{$td_e}
{$td}{$td_e}
{$td}<INPUT TYPE="submit" NAME="submit" VALUE="Change">{$td_e}
{$tr_e}
</FORM>
{$table_e}

{* ---------------------------------------------------------------- *
 *
 *	Add a new CA-account for this NREN
 *
 * ---------------------------------------------------------------- *}

<BR />
<H4>Add a new CA NREN-account</H4>

{$table}
<FoRM ACTION="" METHOD="POST">
<INPUT TYPE="hidden" NAME="account" VALUE="add">

{$tr}
{$tr_e}

{$tr}
{$td}Name:{$td_e}
{$td}{$td_e}
{$td}<INPUT TYPE="text" NAME="login_name" VALUE="">{$td_e}
{$td}{$td_e}
{$tr_e}

{$tr}
{$tr_e}

{$tr}
{$td}Password:{$td_e}
{$td}{$td_e}
{$td}<INPUT TYPE="password" NAME="password" VALUE="">{$td_e}
{$tr_e}

{$tr}
{$tr_e}

{$tr}
{$td}{$td_e}
{$td}{$td_e}
{$td}<INPUT TYPE="submit" NAME="submit" VALUE="Create New">{$td_e}
{$tr_e}
</FORM>
{$table_e}


{/if}
