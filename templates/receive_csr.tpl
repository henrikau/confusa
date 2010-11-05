<h3>3. Upload/create a certificate signing request (CSR)</h3>
<a href="#" id="infoHeader" style="margin-bottom: 1em; display: none">More information</a>
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

{literal}
<script type="text/javascript">
	$('#infoHeader').show();
	$('#infoBlock').hide();

	$('#infoHeader').toggle(function () {
		$('#infoBlock').toggle();
		$('#infoHeader').html('Less information');
	},
		function() {
		$('#infoBlock').toggle();
		$('#infoHeader').html('More information');
	});
</script>
{/literal}

<div class="tabheader">
  <ul class="tabs">
    <li>{if isset($browser_csr)}<span>{$l10n_tab_browsergen}</span>{else}<a href="?show=browser_csr&amp;{$ganticsrf}">{$l10n_tab_browsergen}</a>{/if}</li>
    <li>{if isset($upload_csr)}<span>{$l10n_tab_uploadcsr}</span>{else}<a href="?show=upload_csr&amp;{$ganticsrf}">{$l10n_tab_uploadcsr}</a>{/if}</li>
    <li>{if isset($paste_csr)}<span>{$l10n_tab_pastecsr}</span>{else}<a href="?show=paste_csr&amp;{$ganticsrf}">{$l10n_tab_pastecsr}</a>{/if}</li>
</ul>
</div>
<div class="spacer"></div>

<form id="startForm" method="post" action="{$nextScript}">
<fieldset>

{if isset($browser_csr)}
<legend>Generate a CSR in the browser</legend>
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
      <table>
	<tr>
	  <td>
	    <div><!-- XHTML strict won't allow inputs just within forms -->
	      <input type="hidden" name="uploadedCSR" value="uploadedCSR" />
	      <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	      <input type="file" name="user_csr" />
	      {$panticsrf}
	      <input type="submit"
		     value="{$l10n_button_uploadcsr}"
		     onclick="return isBoxChecked(aup_box);" />
	    </div>
	  </td>
	</tr>
      </table>
      <br />
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
	    <textarea name="user_csr" rows="20" cols="70"></textarea><br />
	  </td>
	</tr>
	<tr>
	  <td><div class="spacer"></div></td>
	  <td></td>
	</tr>
    </table>
{/if}

</fieldset>
<div style="float: right;" class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" class="nav" value="next >" />
</div>
</form>

<div style="float: right;" class="nav">
<form action="confirm_aup.php?{$ganticsrf}" method="get">
	<input id="backButton" class="nav" type="submit" value="< back" />
</form>
</div>
