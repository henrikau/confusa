<div class="tabheader">
<ul class="tabs">
<li><a href="?show=text">{$l10n_tab_texts}</a></li>
<li><a href="?show=css">{$l10n_tab_css}</a></li>
<li><a href="?show=logo">{$l10n_tab_logo}</a></li>
<li><span>{$l10n_tab_notificationmail}</span></li>
</ul>
</div>
<br />
<fieldset>
	<legend>{$l10n_legend_notmail}</legend>
	<p class="info">
		{$l10n_infotext_notmail1}
	</p>
	<p class="info">
	{$l10n_infotext_notmail2} {literal}{$varname}{/literal} {$l10n_infotext_notmail3} {$person->getEmail()}" {$l10n_infotext_notmail4}
	</p>
	<div style="padding-top: 1em; padding-bottom: 2em">
		<span style="font-style: italic">
		<a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span>{$l10n_link_mailvars}</a>
		</span>
		<div class="expcont">
		{if count($tags) > 0}
			<ul style="margin-left: 5%; margin-top: 1em; font-style: italic">
				{foreach from=$tags item=tag}
					<li>{literal}{${/literal}{$tag}{literal}}{/literal}</li>
				{/foreach}
			</ul>
		{/if}
		</div>
	</div>

	<form action="" method="post">
	<div style="width: 90%">
		<textarea style="width: 100%" name="mail_content" rows="20" cols="80">{$mail_content}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%">
		<span style="float: left">
			<input type="submit" name="test" value="{$l10n_button_sendto} {$person->getEmail()}"/>
		</span>
		<span style="float: right">
			 <input type="submit" name="reset" value="Reset"
			        onclick="return confirm('{$l10n_confirm_mailreset}')" />
			<input type="submit" name="change" value="{$l10n_button_update}" />
		</span>
		<input type="hidden" name="stylist_operation" value="change_mail" />
	</div>
	</form>
	<br />
	<br />
</fieldset>
