<fieldset>
	{* *********************************************************************** *}
	{* ***** subscriber-admin/subscriber-admin view ***** *}
	{* *********************************************************************** *}
	<legend>
 Subscriber admins
</legend>

	<p class="info">
	Add/delete administrators for your institution '{$subscriber|escape}'.
	You yourself are marked with an asterisk (*). Subscriber admins have the
	following privileges:
	</p>
	<ul class="info">
	<li>Revoke certificates of users of their own institution</li>
	<li>Add/delete other subscriber admins and subscriber-subadmins</li>
	</ul>

	{if !empty($subscriber_admins)}
	<table>
	<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px"></td><td style="width: 15em"><b>Principal identifier</b></td>
	<td style="width: 15em"><b>Admin name</b></td>
	</tr>
		<tr>
		<td style="height: 1em"></td>
		</tr>
		{foreach from=$subscriber_admins item=subscriber_admin}
		<tr>
		<td style="width: 30px">
		<form action="" method="post">
			<div>
			<input type="hidden" name="subs_operation" value="downgrade_subs_admin" />
			<input type="hidden" name="subs_admin" value="{$subscriber_admin.eppn}" />
			{if ($subscriber_admin.eppn == $self)}
			<input type="image" src="graphics/arrow_down.png" alt="Downgrade"
			title="Downgrade admin"
			name="Downgrade" onclick="return confirm('Downgrade YOURSELF to subscriber sub-admin status? Are you sure?')" />
			{else}
			<input type="image" src="graphics/arrow_down.png" alt="Downgrade"
			title="Downgrade admin"
			name="Downgrade" onclick="return confirm('Downgrade admin {$subscriber_admin.eppn|escape} to subscriber sub-admin status?')" />
			{/if}
			</div>
		</form>
		</td>
		<td style="width: 30px">
		<form action="" method="post">
		<div>
				<input type="hidden" name="subs_operation" value="delete_subs_admin" />
				<input type="hidden" name="subs_admin" value="{$subscriber_admin.eppn}" />
		{if ($subscriber_admin.eppn == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="Delete admin"
				name="delete" onclick="return confirm('You are about to delete YOURSELF!\nAre you sure?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin.eppn|escape} <span style="cursor:help" title="That's you!">(*)</span></td>
			<td>{$subscriber_admin.name|escape|default:"<i>not assigned yet</i>"}</td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="Delete admin"
				name="delete" onclick="return confirm('Delete entry {$subscriber_admin.eppn|escape}?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin.eppn|escape}</td>
			<td>{$subscriber_admin.name|escape|default:"<i>not assigned yet</i>"}</td>
		{/if}
		</tr>
		<tr>
		{* air *}
		<td style="height: 0.5em"></td>
		</tr>
		{/foreach}
		</table>
	{/if}

	<form action="" method="post">
	<table>
	<tr>
	<td style="height: 1em"></td>
	</tr>
	<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px">
	</td>
	<td style="width: 15em">
		<input type="hidden" name="subs_operation" value="add_subs_admin" />
		<input type="text" name="subs_admin" />
	</td>
	<td style="width: 15em">
		<input type="text" disabled="disabled" value="Assigned at first login" />
	</td>
	<td>
		<input type="submit" name="add" value="Add new" />
	</td>
	</tr>
	</table>
	</form>

</fieldset>

<div class="spacer"></div>

{* *********************************************************************** *}
{* ***** subscriber-admin/sub-subscriber-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
<legend>
	Subscriber sub-admins
</legend>

<p class="info">
Add/delete subscriber-subadmins for your institution '{$subscriber|escape}'.
Subscriber sub-admins have the following privileges:
</p>
	<ul class="info">
	<li>Revoke certificates of users of their own institution</li>
	</ul>

{if !empty($subscriber_sub_admins)}
<table>
<tr>
<td style="width: 30px"></td>
<td style="width: 30px"></td>
<td style="width: 15em"><b>Principal identifier</b></td>
<td style="width: 15em"><b>Admin name</b></td>
</tr>
<tr>
<td style="height: 1em"></td>
</tr>
{foreach from=$subscriber_sub_admins item=admin}
	<tr>
		<td style="width: 30px">
			<form action="" method="post">
			<div>
				<input type="hidden" name="subs_operation" value="upgrade_subs_sub_admin" />
				<input type="hidden" name="subs_sub_admin" value="{$admin.eppn}" />
				<input type="image" src="graphics/arrow_up.png" alt="Upgrade admin"
				title="Upgrade admin" name="upgrade"
				onclick="return confirm('Upgrade admin {$admin.eppn|escape} to subscriber-admin of subscriber {$subscriber|escape}?')" />
			</div>
			</form>
		</td>
		<td style="width: 30px">
			<form action="" method="post">
			<div>
			<input type="hidden" name="subs_operation" value="delete_subs_sub_admin" />
			<input type="hidden" name="subs_sub_admin" value="{$admin.eppn}" />
			<input type="image" src="graphics/delete.png" alt="Delete entry"
			title="Delete admin"
			name="delete" onclick="return confirm('Delete entry {$admin.eppn|escape}?')" />
			</div>
		</form>
		</td>
		<td>{$admin.eppn|escape}</td>
		<td>{$admin.name|escape|default:"<i>not assigned yet</i>"}</td>
	</tr>
	{* air *}
	<tr>
	<td style="height: 0.5em"></td>
	</tr>
{/foreach}
</table>
{/if}

<form method="post" action="">
<table>
<tr>
<td style="height: 1em"></td>
</tr>
<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px"></td>
	<td style="width: 15em">
		<input type="hidden" name="subs_operation" value="add_subs_sub_admin" />
		<input type="text" name="subs_sub_admin" />
	</td>
	<td style="width: 15em">
		<input type="text" disabled="disabled" value="Assigned at first login" />
	</td>
	<td>
		<input type="submit" name="add" value="Add new" />
	</td>
</tr>
</table>
</form>
</fieldset>

<div class="spacer"></div>
<div class="spacer"></div>
{* ************************************************************************* *}
{* ************** Subscriber-admin/NREN-admin view ***************           *}
{* ************************************************************************* *}
<h3><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> Your super administrators</a></h3>
<div class="expcont">
	<div class="spacer"></div>
	<fieldset class="infoblock">
		<legend>Admins for your NREN {$nren|escape}</legend>
		<p class="info">
		NREN-admins are administrating the whole NREN admin domain, i.e. your institution
		along with other institutions. They can also define, which institutions are
		hooked up to Confusa and which credentials should be used for communicating
		with the Online-CA. Below you can find a list of them along with their e-mail addresses your NREN {$nren|escape}:
		</p>
		<ul>
		{foreach from=$nren_admins item=nren_admin}
			<li>{$nren_admin.eppn|escape} ({$nren_admin.email|escape|default:"<i>not assigned yet</i>"})</li>
		{/foreach}
		</ul>
	</fieldset>
</div>
