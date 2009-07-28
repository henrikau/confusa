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
    {* Revoke the certificates from a single cert-owner *}
    {if $revoke_cert}
        <div class="admin_table">
        <div class="admin_table_row">
            <div class="admin_table_cell">
                <b>Full Subject DN</b>
            </div>
            <div class="admin_table_cell">
                <b>Revocation reason</b>
            </div>
            <div class="admin_table_cell"></div>
        </div>

        {foreach from=$owners item=owner}
            <div class="admin_table_row">
                <div class="admin_table_cell">
                    <form action="?revoke=do_revoke" method="POST">
                    {$owner}
                </div>
                <div class="admin_table_cell">
                    {foreach from=$orders[$owner] item=order}
                        <input type="hidden" name="order_numbers[]" value={$order} />
                    {/foreach}

                    {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
                </div>
                <div class="admin_table_cell">
                    <input type="submit" name="submit" value="Revoke all" onclick="return confirm('Are you sure?')" />
                </div>

                </form>
            </div>
        {/foreach}
        </div>

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

    {/if}
{/if}

