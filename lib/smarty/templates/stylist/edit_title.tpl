<div class="tabheader">
<ul class="tabs">
<li><a href="?show=texts">{$l10n_tab_texts}</a></li>
<li><a href="?show=css">{$l10n_tab_css}</a></li>
<li><a href="?show=logo">{$l10n_tab_logo}</a></li>
<li><span>{$l10n_tab_portaltitle}</span></li>
<li><a href="?show=mail">{$l10n_tab_notificationmail}</a></li>
</ul>
</div>

{literal}
<script type="text/javascript">
function toggleForm(checkbox)
{
	var titleField = document.getElementById("portalTitleField");

	if (checkbox.checked == false) {
		titleField.disabled = true;
	} else {
		titleField.disabled = false;
	}
}
</script>
{/literal}

<div>
<fieldset>
<legend>{$l10n_legend_portaltitle}</legend>
<p class="info">
{$l10n_infotext_portaltitle}
</p>

<form method="post" action="">
<div style="margin-bottom: 0.5em">
<input type="hidden" name="stylist_operation" value="change_title" />
<input id="portalTitleField" type="text" name="portalTitle" value="{$portalTitle}" {if !$showPortalTitle}disabled="disabled"{/if}/>
</div>
<div style="margin-bottom: 0.5em">
<input onclick="return toggleForm(this);" type="checkbox" id="showPortalTitle" name="showPortalTitle" value="show" {if $showPortalTitle}checked="checked"{/if}/>
<label for="showPortalTitle">{$l10n_label_showportaltitle}</label>
</div>
<div>
<input type="submit" name="changeButton" value="{$l10n_button_change}" />
</div>
</form>
</fieldset>
</div>

