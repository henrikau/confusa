{if ! $person->isAuth()}
{assign var=prot_title_prefix value='title="'}
{assign var=prot_title_suffix value='"'}
{assign var=prot_title value=$prot_title_prefix$prot_title_text1$prot_title_suffix}
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
<h3><a href="index.php?start_login=yes">{$item_login|escape}</a></h3>
{else}
<h3><a href="{$logoutUrl}">{$item_logout|escape}</a></h3>
{/if}
