{literal}
<script type="text/javascript">
	function showHint()
	{
		var hint = document.getElementById('hint');
		hint.setAttribute("style", "display: block; font-size: 0.8em; font-style: italic");
		hint.style.cssText = "display: block; font-size: 0.8em; font-style: italic";
	}

	function hideHint()
	{
		var hint = document.getElementById('hint');
		hint.setAttribute("style", "display: none; font-size: 0.8em; font-style: italic");
		hint.style.cssText = "display: none; font-size: 0.8em; font-style: italic";
	}

</script>
{/literal}

{* Offer the NREN-admin a subscriber pre-selection *}
All revocation operations currently limited to subscriber {$subscriber|escape}.
<div class="spacer"></div>
<div style="text-align: right">
    <form action="" method="post">
    <div>
    Select subscriber (orgname in the DN):
    {html_options name="subscriber" values=$subscribers output=$subscribers selected=$subscriber|escape}
    <input type="submit" name="change" value="Change" />
    </div>
    </form>
</div>


{* The search part *}
{* A normal person isn't offered any search options. Instead, he/she will
immediately see a result entry *}

<div class="spacer"></div>
<form action="" method="post">
<fieldset id="inputField">
<legend>CN-search ({$subscriber|escape})</legend>

<p class="info">
Search for a commonName or a eduPersonPrincipalName of a person within the
institution {$subscriber|escape} whose certificates you want to revoke. Use '%' as a
wildcard.
</p>
<br />
<p class="info">Example: "John Doe jdoe@example.org" or "%jdoe@example.org".</p>
<br />
<input onblur="hideHint();" onfocus="showHint();" type="text" name="search" value="" />
<input type="hidden" name="revoke_operation" value="search_by_cn" />
<input type="hidden" name="subscriber" value="{$subscriber}" />
<input type="submit" name="Search" value="Search" />
<br />
<noscript>
<span style="font-size: 0.8em; font-style: italic">input is case sensitive</span>
</noscript>
<span id="hint" style="display: none; font-size: 0.8em; font-style: italic">input is case sensitive</span>
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
                    {$owner|escape|replace:',':', '}
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
