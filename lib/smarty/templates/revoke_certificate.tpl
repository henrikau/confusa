{if $person->inAdminMode()}
    <h3>Certificate Revocation Area</h3>
    <div class="spacer"></div>

    {if $person->isNRENAdmin()}
        {include file='revocation/persp_nren_admin.tpl'}
    {elseif $person->isSubscriberAdmin() || $person->isSubscriberSubAdmin()}
        {include file='revocation/persp_subscriber_admin.tpl'}
    {/if}

{else}
    {if !isset($owners)}
    <div class="spacer"></div>
    Found no valid certificates to revoke for DN<br /><b>{$person->getX509ValidCN()}</b>!
    {/if}
{/if}
