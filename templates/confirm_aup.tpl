<h3>{$l10n_heading_step1aup}</h3>
<form action="confirm_aup.php" method="post">
<fieldset>
<legend>{$l10n_aup_title}</legend>

<div class="spacer"></div>

<p style="text-align: center; font-style: italic; font-weight: bold">
  <input type="checkbox"
	 name="aup_box"
	 id="aup_box"
	 value="user_agreed"
	 {if $aup_session_state == true}checked="checked"{/if}
	 />
  <label for="aup_box">{$l10n_aup_agreement}</label>
</p>

<a href="#" id="aupExpandable" class="eh_head">{$l10n_aup_info_short}</a>
    <div class="eh_toggle_container">
		<p>{$l10n_aup_info_long1} <a href="{$cps}">{$l10n_aup_info_long2}</a> {$l10n_aup_info_long3} </p>
		<ul style="margin: 1em 0 2em 2em">
			<li>{$l10n_aup_item1}</li>
			<li>{$l10n_aup_item2}</li>
			<li>{$l10n_aup_item3}</li>
			<li>{$l10n_aup_item4}</li>
			<li>{$l10n_aup_item5}</li>
		</ul>
    </div>


<script type="text/javascript">
	$("#aupExpandable").toggle(
		function () {literal}{{/literal}
			$(this).html("{$l10n_item_lessinfo}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$l10n_item_moreinfo}"); {literal}}{/literal}
	);
</script>


{if strlen($privacy_notice_text) > 0}
<div style="margin: 2em 0 0 0">
 <a href="#" id="privnoticeExpandable"
    class="eh_head">{$l10n_header_privacynotice}</a>
 <div class="eh_toggle_container">
   {$privacy_notice_text}
 </div>
</div>
{/if}

<div class="spacer"></div>
</fieldset>

<div style="float: right;" class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" title="{$l10n_button_next}" value="{$l10n_button_next} &gt;" />
</div>
</form>

<form action="download_certificate.php?{$ganticsrf}" method="get">
<div style="float: right;" class="nav">
	<input type="submit" id="backButton" title="{$l10n_button_back}" value="&lt; {$l10n_button_back}" />
</div>
</form>


{literal}
<script type="text/javascript">
	if (! $("#aup_box").attr('checked') ) {
		$("#nextButton").attr("disabled", true);
	}

	$("#aup_box").click(function() {
		if ( $("#aup_box").attr('checked') ) {
			$("#nextButton").removeAttr("disabled");
		} else {
			$("#nextButton").attr("disabled", true);
		}
	}
	);
</script>
{/literal}
