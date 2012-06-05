<h2>{$l10n_heading_help}</h2>
<div class="spacer"></div>
{*
 *}
{if isset($nren_help_text)}
<div class="spacer"></div>
<h3>{$nren}{$l10n_heading_nrenadvice}</h3>
{$nren_help_text}
{*
 *}
{elseif isset($nren_unset_help_text) && isset($nren_contact_email)}
{$nren_unset_help_text|escape}
<p class="center">
<a href="mailto:{$nren_contact_email|escape}">{$nren_contact_email|escape}</a>
</p>
{*
 *}
{else}
<div class="spacer"></div>
{* Generic, NREN-independent help can be here *}
{$help_file}
{/if}
