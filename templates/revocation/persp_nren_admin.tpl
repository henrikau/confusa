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
 {$l10n_infotext_revnrena1}
</p>
<p class="info">
 {$l10n_infotext_revnrena2}
</p>
<br />

{* Offer the NREN-admin a subscriber pre-selection *}
{if $active_subscriber}

{* The search part *}
<fieldset id="inputField">
  <legend>{$l10n_legend_searchcert}</legend>
  <p class="info">
  {$l10n_infotext_searchinfo1} {$active_subscriber|escape} {$l10n_infotext_searchinfo2}
  </p>

  <p class="info">
    {$l10n_infotext_searchexmpl}
  </p>

  <form action="" method="post">
    <p>
      <input onblur="hideHint();"
	     onfocus="showHint();"
	     type="text"
	     name="search"
	     {if $search_string != ""}
	     value="{$search_string|escape}"
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
    <input type="submit" name="Search" value="{$l10n_button_search}" />
    </p>

    <noscript>
      <p>
	<span style="font-size: 0.8em; font-style: italic">
	  {$l10n_warn_input_cs}
	</span>
      </p>
    </noscript>
      <p>
	<span id="hint"
	      style="display: none; font-size: 0.8em; font-style: italic">
	  {$l10n_warn_input_cs}
	</span>
      </p>

  </form>
  <br />
</fieldset>

{* The display part *}

{if isset($owners)}
    {if $revoke_cert}
	<br />
	<h4>{$l10n_info_resultsfound}"<i>{$search_string|escape}</i>"</h4>
	{foreach from=$owners item=owner}
	{include file='revocation/revoke_cert_set.tpl'}
	{/foreach}
    {/if}
{/if}

{else} {* subscriber is null *}
	{$l10n_msg_nosubscribers}
{/if}
