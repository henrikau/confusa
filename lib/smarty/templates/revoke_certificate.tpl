<h3>{$l10n_heading_revocation|escape}</h3>
<div class="spacer"></div>

{if $person->inAdminMode()}
    {if $person->isNRENAdmin()}
        {include file='revocation/persp_nren_admin.tpl'}
    {elseif $person->isSubscriberAdmin() || $person->isSubscriberSubAdmin()}
        {include file='revocation/persp_subscriber_admin.tpl'}
    {/if}
{else}
	<p class="info">
	  {$l10n_text_uinfoall|escape}
	</p>
	<p class="info">
	  {$l10n_text_uinfospec1|escape} <a href="download_certificate.php">{$l10n_menuitem_mycerts|escape}</a> {$l10n_text_uinfospec2|escape}
	</p>
	{* The display part *}
	{if isset($owners)}
		{if $revoke_cert}
			{foreach from=$owners item=owner}
				{include file='revocation/revoke_cert_set.tpl'}
			{/foreach}
		{/if}
	{/if}
{/if}
