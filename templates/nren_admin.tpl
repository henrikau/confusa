
{if $person->isNRENAdmin()}
<h3>{$l10n_heading_managesubs}</h3>

{if isset($add_subscriber)}
<div class="tabheader">
<ul class="tabs">
<li><a href="?target=list">{$l10n_tab_listsubs}</a></li>
<li><span>{$l10n_tab_addsubs}</span></li>
</ul>
</div>
{include file='nren/add_subscriber.tpl'}
<div class="tabheader">
<ul class="tabs">
<li><a href="?target=list">{$l10n_tab_listsubs}</a></li>
<li><span>{$l10n_tab_addsubs}</span></li>
</ul>
</div>

{else if isset($list_subscribers)}
<div class="tabheader">
<ul class="tabs">
<li><span>{$l10n_tab_listsubs}</span></li>
<li><a href="?target=add">{$l10n_tab_addsubs}</a></li>
</ul>
</div>
{include file='nren/list_subscribers.tpl'}
<div class="tabheader">
<ul class="tabs">
<li><span>{$l10n_tab_listsubs}</span></li>
<li><a href="?target=add">{$l10n_tab_addsubs}</a></li>
</ul>
</div>
{/if}

{/if} {* if user is admin *}

