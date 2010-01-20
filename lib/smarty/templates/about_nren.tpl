{if $person->isAuth()}
<h2>{$l10n_heading_aboutnren}</h2>
<div class="spacer"></div>

{if !is_null($text_info)}
	{$text_info}
{/if}

{else}
{include file='unclassified_intro.tpl'}
{/if}
