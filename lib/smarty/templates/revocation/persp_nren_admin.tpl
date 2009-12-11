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
  You have the access-level of NREN-administrator. This means that you
  can revoke <b>all</b> certificates for <b>all</b> users within
  your <b>entire</b> constituency.
</p>
<p class="info">
  You should therefore take care by
  making sure the search-string is spelled correctly, and that the
  returned results make sense.
</p>
<br />

{* Offer the NREN-admin a subscriber pre-selection *}
{if $active_subscriber}

{* The search part *}
<fieldset id="inputField">
  <legend>Search for certificates</legend>
  <p class="info">
    Search for a commonName or a eduPersonPrincipalName of a person within
    the institution {$active_subscriber|escape} whose certificates you want
    to revoke. Use '%' as a wildcard.
  </p>

  <p class="info">
    Example: "John Doe jdoe@example.org" or "%jdoe@example.org".
  </p>

  <form action="" method="post">
    <input onblur="hideHint();"
	   onfocus="showHint();"
	   type="text"
	   name="search"
	   {if $search_string != ""}
	   value="{$search_string}"
	   {/if}
	   />
    <input type="hidden"
	   name="revoke_operation"
	   value="search_by_cn" />

    <select name="subscriber">
      {foreach from=$subscribers item=nren_subscriber}
      {if $nren_subscriber->getOrgName() == $active_subscriber}
      <option value="{$nren_subscriber->getOrgName()}" {*"*}
	      selected="selected">
	{$nren_subscriber->getOrgName()}
      </option>
      {else}
      <option value="{$nren_subscriber->getOrgName()}">
	{$nren_subscriber->getOrgName()}
      </option>
      {/if}
      {/foreach}
    </select>

    <input type="submit" name="Search" value="Search" /><br />

    <noscript>
      <p>
	<span style="font-size: 0.8em; font-style: italic">
	  input is case sensitive
	</span>
      </p>
    </noscript>
    <span id="hint" style="display: none; font-size: 0.8em; font-style:
    italic">input is case sensitive</span>

  </form>
  <br />
</fieldset>

{* The display part *}

{if isset($owners)}
    {if $revoke_cert}
	<br />
	<h4>Found results for search:"<i>{$search_string}</i>"</h4>
	{foreach from=$owners item=owner}
	{include file='revocation/revoke_cert_set.tpl'}
	{/foreach}
    {/if}
{/if}

{else} {* subscriber is null *}
	No subscriber is currently available. You can therefore not revoke any
	certificates (since you have none available).
{/if}
