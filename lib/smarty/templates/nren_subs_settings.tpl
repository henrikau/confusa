{if $person->inAdminMode() && ($person->isNRENAdmin() || $person->isSubscriberAdmin())}

<div class="spacer"></div>

{if $person->isNRENAdmin()}

{include file='nren/nren_info.tpl'}

{elseif $person->isSubscriberAdmin()}

{include file='nren/subscr_info.tpl'}

{/if}

{else}
{* User is not admin *}
{include file='not_authorized.tpl'}
{/if}
