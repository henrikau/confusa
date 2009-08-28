<h3><a href="javascript:void(0)" onclick="toggleExpand(this)"><span class="expchar">+</span> Your super administrators</a></h3>
<div class="expcont">
	<div class="spacer"></div>
	<fieldset class="infoblock">
		<legend>Admins for your institution {$subscriber}</legend>
		<p class="info">
		The following are the subscriber admins that are administrating your institution:
		</p>
		<br />
		<ul>
		{foreach from=$subscriber_admins item=subscriber_admin}
			<li>{$subscriber_admin}</li>
		{/foreach}
		</ul>
	</fieldset>
	<div class="spacer"></div>
	<div class="spacer"></div>
</div>
{* Show infoblock for subscriber sub-admins only if they include any other admins but the admin herself *}
{if empty($subscriber_sub_admins) === FALSE}
	<h3><a href="javascript:void(0)" onclick="toggleExpand(this)"><span class="expchar">+</span> Your fellow administrators</a></h3>
	<div class="expcont">
		<div class="spacer"></div>
		<fieldset class="infoblock">
			<legend>Subadmins for your institution {$subscriber}</legend>
			<p class="info">
			The following are sub-admins for your insitutions, who, like you, may revoke
			certificates:
			</p>
			<br />
			<ul>
			{foreach from=$subscriber_sub_admins item=sub_admin}
				<li>{$sub_admin}</li>
			{/foreach}
			</ul>
		</fieldset>
	</div>
{/if}