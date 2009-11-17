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

{* The search part *}

{* A normal person isn't offered any search options. Instead, he/she will
immediately see a result entry *}

    <div class="spacer"></div>
    <form action="" method="post">
    <fieldset>
    <legend>CN-search</legend>

    <p class="info">Search for a commonName or a eduPersonPrincipalName of a
    person within your institution whose certificates you want to revoke. Use
    '%' as a wildcard.
    </p>
    <br />
	<p class="info">Example: "John Doe jdoe@example.org" or "%jdoe@example.org".</p>
	<br />
    <input type="hidden" name="revoke_operation" value="search_by_cn" />
    <input type="text" name="search" value="" />
    <input type="submit" name="Search" value="Search" />
	<br />
	<noscript>
	<span style="font-size: 0.8em; font-style: italic">input is case sensitive</span>
	</noscript>
	<span id="hint" style="display: none; font-size: 0.8em; font-style: italic">input is case sensitive</span>
    </fieldset>
    </form>

    <div class="spacer"></div>

    <form enctype="multipart/form-data" action="" method="post">
    <fieldset>
    <legend>List upload</legend>

    <p class="info">Upload a comma separated list of eduPersonPrincipalNames whose
    certificates should be revoked. You will be asked for confirmation before the certificates
    will actually be revoked. Separate the ePPNs in the list with a ',' comma.
    </p>
    <br />
    <input type="hidden" name="revoke_operation" value="search_by_list" />
    <input type="hidden" name="max_file_size" value="10000000" />
    <input name="{$file_name}" type="file" />
    <input type="submit" value="Upload list" />
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

    {* Revoke the certificates from a list of cert-owners *}
    {elseif $revoke_list}
        <b>The following DNs are going to be revoked:</b><br />
        <div class="spacer"></div>
        <table class="small">

        {foreach from=$owners item=owner}
            <tr style="width: 80%">
                <td>{$owner|escape}</td>
            </tr>
        {/foreach}

        </table>

        <div class="spacer"></div>
        <div style="text-align: right">
            <form action="" method="post">
            Revocation reason:
            {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
            <input type="hidden" name="revoke_operation" value="revoke_by_list" />
            <input type="Submit" value="Revoke all" onclick="return confirm('Are you sure?')" />
            </form>
        </div>

    {/if}
{/if}

