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
  {$l10n_infotext_revsubsa1} ({$subscriber->getOrgName()}). {$l10n_infotext_revsubsa2}
</p>
<p class="info">
  {$l10n_infotext_revsubsa3}
</p>

{* The search part *}

{* A normal person isn't offered any search options. Instead, he/she will
immediately see a result entry *}

    <div class="spacer"></div>
    <form action="" method="post">
    <fieldset>
    <legend>{$l10n_legend_searchcert}</legend>

    <p class="info">{$l10n_infotext_searchinfo3} {$l10n_infotext_searchinfo2}
    </p>
	<p class="info">{$l10n_infotext_searchexmpl}</p>
    <input type="hidden" name="revoke_operation" value="search_by_cn" />
    <input onblur="hideHint();"
	   onfocus="showHint();"
	   type="text"
	   name="search"
	   {if $search_string != ""}
	   value="{$search_string}"
	   {/if}
	   />
    <input type="submit" name="Search" value="{$l10n_button_search}" />
	<br />
	<noscript>
	  <p>
	    <span style="font-size: 0.8em; font-style: italic">
	      {$l10n_warn_input_cs}
	    </span>
	  </p>
	</noscript>
	<span id="hint" style="display: none; font-size: 0.8em; font-style: italic">{$l10n_warn_input_cs}</span>
    <br />
    </fieldset>
    </form>
<br />
<br />

    <fieldset>
    <legend>{$l10n_legend_listupload}</legend>

    <p class="info">
      {$l10n_infotext_listupload1}
    </p>

    <form enctype="multipart/form-data" action="" method="post">
      <p>
	<input type="hidden" name="revoke_operation" value="search_by_list" />
	<input type="hidden" name="max_file_size" value="10000000" />
	<input name="{$file_name}" type="file" />
	<input type="submit" value="{$l10n_button_uploadlist}" />
      </p>
    </form>
    <br />
    </fieldset>

    <br />
    <br />
{* The display part *}

{if isset($owners)}
    {if isset($revoke_cert) && $revoke_cert === TRUE}
        {foreach from=$owners item=owner}
		{include file='revocation/revoke_cert_set.tpl'}
        {/foreach}

    {* Revoke the certificates from a list of cert-owners *}
    {elseif $revoke_list}
        <b>{$l10n_info_listrevoke1}</b><br />
        <div class="spacer"></div>

        {foreach from=$owners item=owner}
            <div style="width: 80%;">
                {$owner|escape}
            </div>
        {/foreach}

        <div class="spacer"></div>
        <div style="text-align: right">
            <form action="" method="post">
            {$l10n_listrevoke_reas1}
            {html_options name="reason" values=$nren_reasons output=$nren_reasons selected=$selected}
            <input type="hidden" name="revoke_operation" value="revoke_by_list" />
            <input type="submit" value="Revoke all" onclick="return confirm('{$l10n_confirm_listrevoke}')" />
            </form>
        </div>

    {/if}
{/if}

