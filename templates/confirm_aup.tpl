<h3>1. Agree to the acceptable use policy</h3>
<form action="confirm_aup.php" method="post">
<fieldset>
<legend>{$l10n_aup_title}</legend>

<div class="spacer"></div>

<p style="text-align: center; font-style: italic; font-weight: bold">
  <input type="checkbox"
	 name="aup_box"
	 id="aup_box"
	 value="user_agreed"
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

{literal}
<script type="text/javascript">
	$("#aupExpandable").toggle(
		function () {
			$(this).html("less information"); },
		function() {
			$(this).html("more information"); }
	);
</script>
{/literal}

{if strlen($privacy_notice_text) > 0}
<div style="margin: 2em 0 0 0">
<strong>{$l10n_header_privacynotice}</strong>: <span id="collapseLink" style="display: none"><a class="privacyNotice" href="#">(less information)</a></span>

<div id="shortPrivacyNotice" style="display: none">
{$privacy_notice_text|truncate:50:"...":true}
<a class="privacyNotice" href="#" title="Click to read full notice">(more information)</a>
</div>

<div id="fullPrivacyNotice">
	{$privacy_notice_text}
</div>
</div>
{/if}

{literal}
<script type="text/javascript">

	/* show only short privacy notice, let user expand if JavaScript available */
	$("#shortPrivacyNotice").show();
	$("#fullPrivacyNotice").hide();

	$(".privacyNotice").click(
		function () {
			$("#fullPrivacyNotice").toggle();
			$("#shortPrivacyNotice").toggle();
			$("#collapseLink").toggle();
		}
	);
</script>
{/literal}

<div class="spacer"></div>
</fieldset>

<div style="float: right;" class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" title="next" value="next >" />
</div>
</form>

<div style="float: right;" class="nav">
<form action="download_certificate.php?{$ganticsrf}" method="get">
	<input type="submit" id="backButton" title="back" value="< back" />
</form>
</div>


{literal}
<script type="text/javascript">
	$("#nextButton").attr("disabled", true);
	$("#aup_box").click(function() {
		if ($('#aup_box').attr('checked') == true) {
			$("#nextButton").removeAttr("disabled");
		} else {
			$("#nextButton").attr("disabled", true);
		}
	}
	);
</script>
{/literal}
