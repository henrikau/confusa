{if $person->isAuth()}
	{if $person->getMode() == 0}
	<h3>Showing normal-mode splash</h3>
	{elseif $person->getMode() == 1}
	<h3>Showing admin-mode splash</h3>
	{/if}
{else}
{include file='unclassified_intro.tpl'}	
{/if}