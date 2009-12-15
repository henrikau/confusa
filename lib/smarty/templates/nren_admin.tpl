
{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN administration</h3>

{if isset($add_subscriber)}
<div class="tabheader">
<ul class="tabs">
<li><a href="?target=list">List subscribers</a></li>
<li><span>Add new</span></li>
</ul>
</div>
{include file='nren/add_subscriber.tpl'}
<div class="tabheader">
<ul class="tabs">
<li><a href="?target=list">List subscribers</a></li>
<li><span>Add new</span></li>
</ul>
</div>

{else if isset($list_subscribers)}
<div class="tabheader">
<ul class="tabs">
<li><span>List subscribers</span></li>
<li><a href="?target=add">Add new</a></li>
</ul>
</div>
{include file='nren/list_subscribers.tpl'}
<div class="tabheader">
<ul class="tabs">
<li><span>List subscribers</span></li>
<li><a href="?target=add">Add new</a></li>
</ul>
</div>
{/if}

{/if} {* if user is admin *}

