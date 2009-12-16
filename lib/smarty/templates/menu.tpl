{if ! $person->isAuth()}
{assign var=prot_title value='title="You will be redirected to login before you can view this page"'}
{assign var=prot_l value="<i>"}
{assign var=prot_r value="*</i>"}
{else}
{assign var=prot_title value=''}
{assign var=prot_l value=''}
{assign var=prot_r value=''}
{/if}


{* ------------------------------------------------------------ *}
{*		If the person is in normal-mode			*}
{* ------------------------------------------------------------ *}
{if $person->getMode() == 0}
{include file='menu/persp_user.tpl'}

{* ------------------------------------------------------------ *}
{*		Person is in admin-mode				*}
{* ------------------------------------------------------------ *}
{elseif $person->getMode() == 1}
	{if $person->isNRENAdmin()}
		{include file='menu/persp_nren_admin.tpl'}
	{elseif $person->isSubscriberAdmin()}
		{include file='menu/persp_subscr_admin.tpl'}
	{elseif $person->isSubscriberSubAdmin()}
		{include file='menu/persp_subscr_subadmin.tpl'}
	{/if}
{/if}


{if !$person->isAuth()}
<h3><a href="index.php?start_login=yes">Login</a></h3>
{else}
<h3><a href="{$logoutUrl}">Log out</a></h3>
{/if}
