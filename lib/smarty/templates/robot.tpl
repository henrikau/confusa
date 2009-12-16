<h3>Robot Interface</h3>
<br />
{* show menu *}
[ <a href="?robot_view=list">List</a> ]
[ <a href="?robot_view=upload">Upload</a>]
[ <a href="?robot_view=info">Info</a>]
<br />
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
