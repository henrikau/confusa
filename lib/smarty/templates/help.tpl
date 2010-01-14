<h2>{$l10n_heading_help}</h2>

{if $person->isAuth()}
<div class="spacer"></div>
<h3>{$nren}{$l10n_heading_nrenadvice}</h3>
{$nren_help_text}
{/if}
<div class="spacer"></div>
{* Generic, NREN-independent help can be here *}
{$help_file}
