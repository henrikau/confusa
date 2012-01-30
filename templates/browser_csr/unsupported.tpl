<h3 id="heading">{$l10n_heading_unsupported}</h3>
<div>
<fieldset>
<div id="info_view">
<p class="info">
	{$l10n_infotext_browsernsup}
</p>
	<ul class="info">
	<li>Google Chrome</li>
	<li>Mozilla Firefox {$l10n_infotext_gecko}</li>
	<li>Microsoft Internet Explorer {$l10n_infotext_browserwinv}</li>
	<li>Opera</li>
	<li>Apple Safari (MacOS X)</li>
	</ul>

	<p class="info">
		{$l10n_infotext_upgrbrowser}
	</p>
</div>
</fieldset>

<div class="nav">
<form action="receive_csr.php?{$ganticsrf}" method="get">
	<input id="backButton" type="submit" title="{$l10n_button_back}" value="&lt; {$l10n_button_back}" />
</form>
</div>
</div>
