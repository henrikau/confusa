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
                    {$owner|escape}
                </td>
                <td>
                    <form action="" method="post">
                    <div>
					<input type="hidden" name="revoke_operation" value="revoke_by_cn" />
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

