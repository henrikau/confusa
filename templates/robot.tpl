<h3>{$l10n_heading_roboti|escape}</h3>
{* show menu *}
[ <a href="?robot_view=list&amp;{$ganticsrf}">{$l10n_tab_rlist}</a> ]
[ <a href="?robot_view=upload&amp;{$ganticsrf}">{$l10n_tab_upload}</a>]
[ <a href="?robot_view=info&amp;{$ganticsrf}">{$l10n_tab_info}</a>]
<br />
<br />

{* display the pages *}
{if $rv_list}
{include file='robot/ri_list.tpl'}
<br />
{/if}

{if $rv_upload}
{include file='robot/ri_upload.tpl'}
<br />
{/if}

{if $rv_info}
{include file='robot/ri_info.tpl'}
<br />
{/if}
