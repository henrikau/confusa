<h2>Help</h2>

{if $person->isAuth()}
<div class="spacer"></div>
<h3>{$nren}'s Advice:</h3>
{$nren_help_text}
{/if}
<div class="spacer"></div>
{* Generic, NREN-independent help can be here *}
{$help_file}
