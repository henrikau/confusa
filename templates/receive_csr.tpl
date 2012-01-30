{if isset($skippedEmail) && $skippedEmail === true}
<div id="skippedEmailSelNot" style="border: 2pt black solid; padding: 0.5em 0.5em 0.5em 0.5em; margin-bottom: 1em">
	{$l10n_msg_skipemail}
</div>

<script type="text/javascript">
	$("#skippedEmailSelNot").fadeOut(7000);
</script>
{/if}

<h3>{$l10n_heading_step3csr}</h3>

<a href="#" id="infoHeader" style="margin-bottom: 1em; display: none">{$l10n_item_moreinfo}</a>
<div id="infoBlock" style="margin-top: 1em">
	<p class="info">
	  {$l10n_infotext_csroverview1}
	</p>
	<p class="info">
	 {$l10n_infotext_csroverview2}
	</p>
	<p class="info">
	  {$l10n_infotext_csroverview3}
	</p>
</div>

<script type="text/javascript">
	$('#infoHeader').show();
	$('#infoBlock').hide();

	$('#infoHeader').toggle(function () {literal}{{/literal}
		$('#infoBlock').slideToggle('slow');
		$('#infoHeader').html('{$l10n_item_lessinfo}');
	{literal}}{/literal},
		function() {literal}{{/literal}
		$('#infoBlock').slideToggle('slow');
		$('#infoHeader').html('{$l10n_item_moreinfo}');
		{literal}}{/literal});
</script>


<div class="tabheader">
  <ul class="tabs">
    <li>{if isset($browser_csr)}<span>{$l10n_tab_browsergen}</span>{else}<a href="?show=browser_csr&amp;{$ganticsrf}">{$l10n_tab_browsergen}</a>{/if}</li>
    <li>{if isset($upload_csr)}<span>{$l10n_tab_uploadcsr}</span>{else}<a href="?show=upload_csr&amp;{$ganticsrf}">{$l10n_tab_uploadcsr}</a>{/if}</li>
    <li>{if isset($paste_csr)}<span>{$l10n_tab_pastecsr}</span>{else}<a href="?show=paste_csr&amp;{$ganticsrf}">{$l10n_tab_pastecsr}</a>{/if}</li>
</ul>
</div>
<div class="spacer"></div>

<form id="startForm" method="post" action="{$nextScript}" {if isset($upload_csr)}enctype="multipart/form-data"{/if}>
<fieldset>

{if isset($browser_csr)}

{literal}
<script type="text/javascript">
	function mayProceed() {
		return true;
	}
</script>
{/literal}

<legend>{$l10n_legend_browsercsr}</legend>
<div class="spacer"></div>
<div id="info_view">
	<p class="info">
		{$l10n_infotext_browsercsr1}
	</p>
</div>
{/if}

{*
 * Upload CSR from file
 *}
{if isset($upload_csr)}
  <legend>{$l10n_legend_uploadnewcsr}</legend>
      <div class="spacer"></div>
      <p class="info">
		{$l10n_infotext_uploadnewcsr1}
      </p>
      <div class="spacer"></div>
	    <div style="margin-bottom: 1em"><!-- XHTML strict won't allow inputs just within forms -->
	      <input type="hidden" name="uploadedCSR" value="uploadedCSR" />
	      <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	      <input id="fileUploader" type="file" name="user_csr" />
	      {$panticsrf}
	    </div>

	{literal}
	<script type="text/javascript">
		function mayProceed() {
			return $("form input[type=file]").val();
		}

		$('#fileUploader').change(function() {
			if (mayProceed()) {
				$('#nextButton').attr("disabled", false);
			} else {
				$('#nextButton').attr("disabled", true);
			}
		});
	</script>
	{/literal}
{/if}

{*
 * uploading new CSR via POST
 *}
{if isset($paste_csr)}
  <legend>{$l10n_legend_pastenewcsr}</legend>
      <div class="spacer"></div>
      <p class="info">
		{$l10n_infotext_pastenewcsr1}
      </p>
      <div class="spacer"></div>
      <p>
	<input type="hidden" name="pastedCSR" value="pastedCSR" />
	{$panticsrf}
      </p>
      <table>
	<tr>
	  <td colspan="2">
	    <textarea id="csrBox" name="user_csr" rows="20" cols="70"></textarea><br />
	  </td>
	</tr>
	<tr>
	  <td><div class="spacer"></div></td>
	  <td></td>
	</tr>
    </table>

	{literal}
	<script type="text/javascript">
		function mayProceed() {
			return $("textarea#csrBox").val();
		}

		$('#csrBox').change(function() {
			if (mayProceed()) {
				$('#nextButton').attr("disabled", false);
			} else {
				$('#nextButton').attr("disabled", true);
			}
		});
	</script>
	{/literal}
{/if}

</fieldset>
<div style="float: right;" class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" {if isset($disable_next_button)}disabled=disabled{/if} class="nav" title="{$l10n_button_next}" value="{$l10n_button_next} &gt;" />
</div>
</form>

{literal}
<script type="text/javascript">
	if (!mayProceed()) {
		$('#nextButton').attr("disabled", true);
	}
</script>
{/literal}

<div style="float: right;" class="nav">
<form action="confirm_aup.php?{$ganticsrf}" method="get">
	<input id="backButton" class="nav" type="submit" value="&lt; {$l10n_button_back}" title="{$l10n_button_back}" />
</form>
</div>
