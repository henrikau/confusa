<fieldset>
	{* *********************************************************************** *}
	{* ***** subscriber-admin/subscriber-admin view ***** *}
	{* *********************************************************************** *}
	<legend>
 Subscriber admins
</legend>

	<p class="info">
	Add/delete administrators for your institution '{$subscriber}'.
	You yourself are marked with an asterisk (*). Subscriber admins have the
	following privileges:
	</p>
	<br />
	<ul class="info">
	<li>Revoke certificates of users of their own institution</li>
	<li>Add/delete other subscriber admins and subscriber-subadmins</li>
	</ul>
	<br />

	<table>
	<tr>
	<td style="width: 30px"></td><td><b>Principal identifier</b></td>
	</tr>
	{if !empty($subscriber_admins)}
		{foreach from=$subscriber_admins item=subscriber_admin}
		<tr>
		<td style="width: 30px">
		<form action="" method="post">
		<div>
				<input type="hidden" name="subs_operation" value="delete_subs_admin" />
				<input type="hidden" name="subs_admin" value="{$subscriber_admin}" />
		{if ($subscriber_admin == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="Delete admin"
				name="delete" onclick="return confirm('You are about to delete YOURSELF!\nAre you sure?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin} <span style="cursor:help" title="That's you!">(*)</span></td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="Delete admin"
				name="delete" onclick="return confirm('Delete entry {$subscriber_admin}?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin}</td>
		{/if}
		</tr>
		{/foreach}
	{/if}

	<tr>
	<td style="width: 30px">
	</td>
	<td>
		<form action="" method="post">
		<div>
			<input type="hidden" name="subs_operation" value="add_subs_admin" />
			<input type="text" name="subs_admin" />
			<input type="submit" name="add" value="Add new" />
		</div>
		</form>
	</td>
	</tr>

</table>
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
Add/delete subscriber-subadmins for your institution '{$subscriber}'.
Subscriber sub-admins have the following privileges:
</p>
<br />
	<ul class="info">
	<li>Revoke certificates of users of their own institution</li>
	</ul>
<br />

<table>

{if !empty($subscriber_sub_admins)}
<tr>
<td style="width: 30px"></td>
<td><b>Principal identifier</b></td>
</tr>
{foreach from=$subscriber_sub_admins item=admin}
	<tr>
		<td style="width: 30px">
			<form action="" method="post">
			<div>
			<input type="hidden" name="subs_operation" value="delete_subs_sub_admin" />
			<input type="hidden" name="subs_sub_admin" value="{$admin}" />
			<input type="image" src="graphics/delete.png" alt="Delete entry"
			title="Delete admin"
			name="delete" onclick="return confirm('Delete entry {$admin}?')" />
			</div>
		</form>
		</td>
		<td>{$admin}</td>
	</tr>
{/foreach}
{/if}

<tr>
	<td style="width: 30px"></td>
	<td>
	<form action="" method="post">
	<div>
		<input type="hidden" name="subs_operation" value="add_subs_sub_admin" />
		<input type="text" name="subs_sub_admin" />
		<input type="submit" name="add" value="Add new" />
	</div>
	</form>
	</td>
</tr>
</table>
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
		<legend>Admins for your NREN {$nren}</legend>
		<p class="info">
		NREN-admins are administrating the whole NREN admin domain, i.e. your institution
		along with other institutions. They can also define, which institutions are
		hooked up to Confusa and which credentials should be used for communicating
		with the Online-CA. Below you can find a list of them for your NREN {$nren}:
		</p>
		<br />
		<ul>
		{foreach from=$nren_admins item=nren_admin}
			<li>{$nren_admin}</li>
		{/foreach}
		</ul>
	</fieldset>
</div>
