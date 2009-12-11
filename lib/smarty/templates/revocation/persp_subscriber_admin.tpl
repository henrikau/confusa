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

<p class="info">
  This is where you can search for certificates belonging to your
  organization ({$subscriber->getOrgName()}). After a search, you will
  be given a number of hits, where each hit represent a <i>set</i> of
  certificates.
</p>
<p class="info">
  It is <b>your</b> responsibility to pick the correct set to revoke.
</p>

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
	<p class="info">Example: "John Doe jdoe@example.org" or "%jdoe@example.org".</p>
    <input type="hidden" name="revoke_operation" value="search_by_cn" />
    <input type="text" name="search" value="" />
    <input type="submit" name="Search" value="Search" />
	<br />
	<noscript>
	  <p>
	    <span style="font-size: 0.8em; font-style: italic">
	      input is case sensitive
	    </span>
	  </p>
	</noscript>
	<span id="hint" style="display: none; font-size: 0.8em; font-style: italic">input is case sensitive</span>
    <br />
    </fieldset>
    </form>
<br />
<br />

    <fieldset>
    <legend>List upload</legend>

    <p class="info">
      Upload a comma separated list of eduPersonPrincipalNames whose
      certificates should be revoked. You will be asked for confirmation
      before the certificates will actually be revoked. Separate the
      ePPNs in the list with a ',' comma.
    </p>

    <form enctype="multipart/form-data" action="" method="post">
      <input type="hidden" name="revoke_operation" value="search_by_list" />
      <input type="hidden" name="max_file_size" value="10000000" />
      <input name="{$file_name}" type="file" />
      <input type="submit" value="Upload list" />
    </form>
    <br />
    </fieldset>

    <br />
    <br />
{* The display part *}

{if isset($owners)}
    {if $revoke_cert}
        {foreach from=$owners item=owner}
		{include file='revocation/revoke_cert_set.tpl'}
        {/foreach}

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

