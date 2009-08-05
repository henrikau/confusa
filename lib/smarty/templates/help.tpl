<h2>Help</h2>

{if $person->is_auth()}
{$nren_help_text}
{/if}

{* Generic, NREN-independent help can be here *}
{$help_file}
