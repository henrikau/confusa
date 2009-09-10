{* Offer the NREN-admin a subscriber pre-selection *}
All revocation operations currently limited to subscriber {$subscriber}.
<div class="spacer"></div>
<div style="text-align: right">
    <form action="" method="post">
    <div>
    Select subscriber:
    {html_options name="subscriber" values=$subscribers output=$subscribers selected=$subscriber}
    <input type="submit" name="change" value="Change" />
    </div>
    </form>
</div>


{* The search part *}
{* A normal person isn't offered any search options. Instead, he/she will
immediately see a result entry *}

<div class="spacer"></div>
<form action="?revoke=search_display" method="post">
<fieldset>
<legend>CN-search ({$subscriber})</legend>

<p class="info">
Search for a commonName or a eduPersonPrincipalName of a person within the
institution {$subscriber} whose certificates you want to revoke. Use '%' as a
wildcard.
</p>
<br />
<input type="text" name="search" value="" />
<input type="hidden" name="subscriber" value="{$subscriber}" />
<input type="submit" name="Search" value="Search" />

</fieldset>
</form>

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
                    <form action="?revoke=do_revoke" method="post">
                    <div>
                    <input type="hidden" name="common_name" value="{$owner}" />

                    {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
                    <input type="submit" name="submit" value="Revoke all"
                            onclick="return confirm('Revoking {$stats[$owner]} certificates! Are you sure?')" />
                    </div>
                    </form>
                </td>
            </tr>
        {/foreach}
        </table>
    {/if}
{/if}