{assign var='table'	value='<div class="admin_table">'}
{assign var='table_e'	value='</div><!--admin_table-->'}
{assign var='tr'	value='<DIV CLASS="admin_table_row">'}
{assign var='tr_e'	value='</DIV><!--admin_table_row-->'}
{assign var='td'	value='<DIV CLASS="admin_table_cell">'}
{assign var='td_e'	value='</DIV><!--admin_table_cell-->'}

<h3>Certificate Revocation Area</h3>

{* The search part *}
{if $person->get_mode() == 0}

{* A normal person isn't offered any search options. Instead, he/she will
immediately see a result entry *}

{else}
    Search for commonName:
    <form action="?revoke=search_display" method="POST">
    <input type="text" name="search" value="" />
    <input type="submit" name="Search" value="Search" />
    </form>

    Or upload a list with eduPersonPrincipalNames to revoke:<br />
    <form enctype="multipart/form-data" action="?revoke=search_list_display" method="POST">
    <input type="hidden" name="max_file_size" value="10000000" />
    <input name="{$file_name}" type="file" />
    <input type="submit" value="Upload list" />
    </form>
{/if}

{* The display part *}

{if isset($owners)}
    {if $revoke_cert}
    <BR />
    <BR />
    <DIV>
    <FIELDSET>
    <LEGEND>Revoke Selected Certificate</LEGEND>
    {$table}
        {$tr}
            {$td}
                <b>Full Subject DN</b>
            {$td_e}
            {$td}
                <b>Revocation reason</b>
            {$td_e}
	    {$td}Expires (from DB){$td_e}
        {$tr_e}

        {foreach from=$owners item=owner}
		{foreach from=$orders[$owner] item=order}
	        {$tr}
			{$td}
				<FORM ACTION="revoke_certificate.php?revoke=do_revoke" METHOD="POST">
				<INPUT TYPE="hidden" NAME="order_number" VALUE="{$order.0}">
				{$owner}
			{$td_e}
			{$td}
				{html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
			{$td_e}
			{$td}
				{$order.1}
			{$td_e}
	                {$td}
				<INPUT TYPE="submit" NAME="submit" VALUE="Revoke" onclick="return confirm('Are you sure?')" />
				</FORM>
			{$td_e}
		{$tr_e}
		{/foreach}
	{/foreach}
    {$table_e}
    </FIELDSET>
    </DIV>

    {* Revoke the certificates from a list of cert-owners *}
    {elseif $revoke_list}
        <b>The following DNs are going to be revoked:</b><br />
        <div class="spacer"></div>
        <table class="small">

        {foreach from=$owners item=owner}
            <tr style="width: 80%">
                <td>{$owner}</td>
            </tr>
        {/foreach}

        </table>

        <div class="spacer"></div>
        <div style="text-align: right">
            <form action="?revoke=do_revoke_list" method="POST">
            Revocation reason:
            {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
            <input type="Submit" value="Revoke all" onclick="return confirm('Are you sure?')" />
            </form>
        </div>

    {else}
	nothing to do
    {/if}


{/if}

