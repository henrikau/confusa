<div class="tabheader">
<ul class="tabs">
<li><span>{$l10n_tab_texts}</span></li>
<li><a href="?show=css">{$l10n_tab_css}</a></li>
<li><a href="?show=logo">{$l10n_tab_logo}</a></li>
<li><a href="?show=title">{$l10n_tab_portaltitle}</a></li>
<li><a href="?show=mail">{$l10n_tab_notificationmail}</a></li>
</ul>
</div>

  <fieldset>
  <legend>{$l10n_legend_changehelptext}</legend>
	<p class="info">
		{$l10n_infotext_help1}
	</p>
	<p class="info">
		{$l10n_infotext_fieldinput}
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
		<input type="hidden" name="stylist_operation" value="change_help_text" />
		<textarea style="width: 100%" name="help_text" rows="10" cols="80">{$help_text}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%; text-align: right">
		<input type="submit" name="change" value="{$l10n_button_change}" />
	</div>
	</form>
  </fieldset>
  <div class="spacer"></div>

  <fieldset>
  <legend>{$l10n_legend_abouttext}</legend>
  <p class="info">
  {$l10n_infotext_abouttext1}
  </p>
  <p class="info">
	{$l10n_infotext_fieldinput}
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
		<input type="hidden" name="stylist_operation" value="change_about_text" />
		<textarea name="about_text" style="width: 100%" rows="10" cols="80">{$about_text}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%; text-align: right">
		<input type="submit" name="change" value="{$l10n_button_change}" />
	</div>
  </form>
  </fieldset>


  <div class="spacer"></div>

  <fieldset>
  <legend>{$l10n_legend_change_privnotice}</legend>
  <p class="info">
    {$l10n_privnotice_1}
  </p>
  <p class="info">
    {$l10n_privnotice_2}
  </p>
  <form action="" method="post">
    <div style="width: 90%">
      <input type="hidden"
	     name="stylist_operation"
	     value="change_privnotice_text" />

      <textarea name="privnotice_text"
		style="width: 100%"
		rows="10"
		cols="80">{$privnotice_text}</textarea>
    </div>
    <div class="spacer"></div>
    <div style="width: 90%; text-align: right">
      <input type="submit" name="change" value="{$l10n_button_change}" />
    </div>
  </form>
  </fieldset>
