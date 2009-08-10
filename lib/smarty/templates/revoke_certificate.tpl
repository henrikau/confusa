{assign var='table'	value='<div class="admin_table">'}
{assign var='table_e'	value='</div><!--admin_table-->'}
{assign var='tr'	value='<DIV CLASS="admin_table_row">'}
{assign var='tr_e'	value='</DIV><!--admin_table_row-->'}
{assign var='td'	value='<DIV CLASS="admin_table_cell">'}
{assign var='td_e'	value='</DIV><!--admin_table_cell-->'}

<h3>Certificate Revocation Area</h3>

{* The search part *}
{if $person->getMode() == 0}

{* A normal person isn't offered any search options. Instead, he/she will
immediately see a result entry *}
{if !isset($owners)}
 <div class="spacer"></div>
    Found no valid certificates to revoke for DN<br /><b>{$person->getX509ValidCN()}</b>!
{/if}

{else}
    <div class="spacer"></div>
    <form action="?revoke=search_display" method="POST">
    <fieldset>
    <legend>CN-search</legend>

    <p class="info">Search for a commonName or a eduPersonPrincipalName of a
    person within your institution whose certificates you want to revoke. Use
    '%' as a wildcard.
    </p>
    <br />
    <input type="text" name="search" value="" />
    <input type="submit" name="Search" value="Search" />
    </fieldset>
    </form>

    <div class="spacer"></div>

    <form enctype="multipart/form-data" action="?revoke=search_list_display" method="POST">
    <fieldset>
    <legend>List upload</legend>

    <p class="info">Upload a comma separated list of eduPersonPrincipalNames whose
    certificates should be revoked. You will be asked for confirmation before the certificates
    will actually be revoked. Separate the ePPNs in the list with a ',' comma.
    </p>
    <br />
    <input type="hidden" name="max_file_size" value="10000000" />
    <input name="{$file_name}" type="file" />
    <input type="submit" value="Upload list" />
    </fieldset>
    </form>
{/if}

{* The display part *}

{if isset($owners)}
    {if $revoke_cert}
        <table>
        <tr>
            <td>
                <b>Full Subject DN</b>
            </td>
            <td>
                <b>Revocation reason</b>
            </td>
            <td></td>
        </tr>

        {foreach from=$owners item=owner}
            <tr>
                <td>
                    {$owner}
                </td>
                <td>
                    <form action="?revoke=do_revoke" method="POST">

                    {foreach from=$orders[$owner] item=order}
                        <input type="hidden" name="order_numbers[]" value="{$order[0]}" />
                        <input type="hidden" name="valid_untill[]" value="{$order[1]}" />
                    {/foreach}

                    {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
                    <input type="submit" name="submit" value="Revoke all"
                            onclick="return confirm('Revoking {$orders[$owner]|@count} certificates! Are you sure?')" />
                    </form>
                </td>

                </form>
            </tr>
        {/foreach}
        </table>

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

