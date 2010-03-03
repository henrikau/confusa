<h3><a href="javascript:void(0)" onclick="toggleExpand(this)"><span class="expchar">+</span> {$l10n_legend_bigboss}</a></h3>
<div class="expcont">
	<div class="spacer"></div>
	<fieldset class="infoblock">
		<legend>{$l10n_legend_subs_admins2} {$subscriber|escape}</legend>
		<p class="info">
		{$l10n_infotext_subs_adm5}:
		</p>
		<ul>
		{foreach from=$subscriber_admins item=subscriber_admin}
			<li> {$subscriber_admin.eppn|escape}
			{if isset($subscriber_admin.email)}
				(<a href="mailto:{$subscriber_admin.email}">{$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</a>)
			{else}
				({$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"})
			{/if}
			</li>
		{/foreach}
		</ul>
	</fieldset>
	<div class="spacer"></div>
	<div class="spacer"></div>
</div>
{* Show infoblock for subscriber sub-admins only if they include any other admins but the admin herself *}
{if empty($subscriber_sub_admins) === FALSE}
	<h3><a href="javascript:void(0)" onclick="toggleExpand(this)"><span class="expchar">+</span> {$l10n_legend_peers}</a></h3>
	<div class="expcont">
		<div class="spacer"></div>
		<fieldset class="infoblock">
			<legend>{$l10n_legend_subss_admins} {$subscriber|escape}</legend>
			<p class="info">
			{$l10n_infotext_subss_adm3}
			</p>
			<ul>
			{foreach from=$subscriber_sub_admins item=sub_admin}
				<li>{$sub_admin.eppn|escape}
				{if isset($sub_admin.email)}
					(<a href="mailto:{$sub_admin.email}">{$sub_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</a>)
				{else}
					({$sub_admin.name|escape|default:"<i>$l10n_info_notassign</i>"})
				{/if}
				</li>
			{/foreach}
			</ul>
		</fieldset>
	</div>
{/if}
