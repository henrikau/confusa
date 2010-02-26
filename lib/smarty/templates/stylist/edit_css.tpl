<div class="tabheader">
<ul class="tabs">
<li><a href="?show=text">{$l10n_tab_texts}</a></li>
<li><span>{$l10n_tab_css}</span></li>
<li><a href="?show=logo">{$l10n_tab_logo}</a></li>
<li><a href="?show=title">{$l10n_tab_portaltitle}</a></li>
<li><a href="?show=mail">{$l10n_tab_notificationmail}</a></li>
</ul>
</div>

<fieldset>
  <legend>{$l10n_legend_customcss}</legend>
  <p class="info">
    {$l10n_infotext_customcss1}
  </p>
  <p class="info">
    {$l10n_infotext_cssinput}
  </p>
  <form action="" method="post">
	<div style="width: 90%">
		<textarea style="width: 100%" name="css_content" rows="20" cols="80">{$css_content}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%">
		<span style="float: left"><input type="submit" name="download" value="{$l10n_button_download}" /></span>
		<span style="float: right">
			 <input type="submit" name="reset" value="{$l10n_button_reset}"
			        onclick="return confirm('{$l10n_confirm_resetcss}')" />
			<input type="submit" name="change" value="{$l10n_button_update}" />
		</span>
		<input type="hidden" name="stylist_operation" value="change_css" />
	</div>
  </form>
</fieldset>
