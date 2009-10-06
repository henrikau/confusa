
{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN administration</h3>
{* show links *}
[ <a href="?target=list">List subscribers</a> ]
[ <a href="?target=add">Add new</a> ]
<br />
<br />

{if $add_subscriber}
{include file='nren/add_subscriber.tpl'}
{/if}
{if $list_subscribers}
{include file='nren/list_subscribers.tpl'}
{/if}


{/if} {* if user is admin *}

<br />
<br />
