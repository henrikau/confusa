{if $person->inAdminMode()}
	{if $person->isNRENAdmin() ||  $person->isSubscriberAdmin()}
		<h3>Add/delete Confusa administrators</h3>
		<div class="spacer"></div>
	{/if}

	{if $person->isNRENAdmin()}
		{include file='admin/persp_nren_admin.tpl'}
	{elseif $person->isSubscriberAdmin()}
		{include file='admin/persp_subscriber_admin.tpl'}
	{elseif $person->isSubscriberSubAdmin()}
		{include file='admin/persp_subscriber_subadmin.tpl'}
	{/if}

{/if}
