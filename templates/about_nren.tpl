<h2>{$l10n_heading_aboutnren}</h2>
<div class="spacer"></div>
{if isset($text_info)}
	{$text_info}
{elseif isset($nren_unset_about_text)}
{$nren_unset_about_text|escape}
<p class="center">
<a href="mailto:{$nren_contact_email|escape}">{$nren_contact_email|escape}</a>
</p>
{/if}
