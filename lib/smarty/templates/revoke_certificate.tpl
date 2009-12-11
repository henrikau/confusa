<h3>Revoke certificates</h3>
<div class="spacer"></div>

{if $person->inAdminMode()}
    {if $person->isNRENAdmin()}
        {include file='revocation/persp_nren_admin.tpl'}
    {elseif $person->isSubscriberAdmin() || $person->isSubscriberSubAdmin()}
        {include file='revocation/persp_subscriber_admin.tpl'}
    {/if}
{else}
	<p class="info">
	  You can now revoke all your certificates. Do to this, you must first
	  specify a reason, and then press 'Revoke all'.
	</p>
	<p class="info">
	  If you want to revoke a specific certificate, you must go
	  to <a href="download_certificate.php">My Certificates</a> and choose
	  the particular certificate to revoke.
	</p>
	{* The display part *}
	{if isset($owners)}
		{if $revoke_cert}
			{foreach from=$owners item=owner}
				{include file='revocation/persp_user.tpl'}
			{/foreach}
		{/if}
	{/if}
{/if}
