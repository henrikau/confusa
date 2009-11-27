
{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN administration</h3>

<br />
{if isset($add_subscriber)}
[ <a href="?target=list">List subscribers</a> ]
[ Add new ]
<br />
<br />
{include file='nren/add_subscriber.tpl'}
<br />
<br />
[ <a href="?target=list">List subscribers</a> ]
[ Add new ]

{else if isset($list_subscribers)}
[ List subscribers ]
[ <a href="?target=add">Add new</a> ]
<br />
<br />
{include file='nren/list_subscribers.tpl'}
<br />
<br />
[ List subscribers ]
[ <a href="?target=add">Add new</a> ]
{/if}

{/if} {* if user is admin *}

