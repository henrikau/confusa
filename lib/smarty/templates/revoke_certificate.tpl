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
                        <input type="hidden" name="order_numbers[]" value={$order.auth_key} />
                        <input type="hidden" name="valid_untill[]" value={$order.valid_untill} />
                    {/foreach}

                    {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
                    <input type="submit" name="submit" value="Revoke all" onclick="return confirm('Are you sure?')" />
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

    {/if}
{/if}

