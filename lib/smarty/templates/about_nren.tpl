{if $person->isAuth()}
<h2>About NREN</h2>
<div class="spacer"></div>

{if !is_null($logo)}
	<div id="logo_in_text">
		<img src="{$logo}" alt="NREN logo" />
	</div>
{/if}

{if !is_null($text_info)}
	{$text_info}
{/if}

{else}
{include file='unclassified_intro.tpl'}
{/if}
