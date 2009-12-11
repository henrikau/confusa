<h3>Revoke certificates</h3>
<div class="spacer"></div>

{if $person->inAdminMode()}
    {if $person->isNRENAdmin()}
        {include file='revocation/persp_nren_admin.tpl'}
    {elseif $person->isSubscriberAdmin() || $person->isSubscriberSubAdmin()}
        {include file='revocation/persp_subscriber_admin.tpl'}
    {/if}
{else}
        {include file='revocation/persp_user.tpl'}
{/if}
