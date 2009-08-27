{* *********************************************************************** *}
{* ***** NREN-admin/NREN-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
<legend>
 NREN admins
</legend>

<p class="info">
		Add and delete NREN admins for your NREN '{$nren}'. You yourself are
		marked with an asterisk (*). NREN admins have many privileges:
	</p>
	<br />
		<ul class="info">
		<li>Add/delete other NREN admins.</li>
		<li>Add/delete subscriber admins.</li>
		<li>Give institutions within the NREN's domain access to Confusa</li>
		<li>Change the NREN's CA account</li>
		<li>Change the branding of the portal</li>
		</ul>

		<br />
		<p class="info">
		Overall this is a very powerful role and you should think who you want
		to give it.
</p>
<br />

<table>
<tr>
<td style="width: 30px"></td><td style="width: 30px"></td><td><b>Principal identifier</b></td>
</tr>

{if !empty($nren_admins)}
	{foreach from=$nren_admins item=admin}
		<tr>
		<td style="width: 30px">
			{if ($admin == $self)}
				<form action ="" method="post">
					<input type="hidden" name="nren_operation" value="downgrade_self" />
					<input type="image" src="graphics/arrow_down.png" alt="Downgrade admin"
					name="Downgrade" title="Downgrade admin"
					onclick="return confirm('Do you want to downgrade YOURSELF to a subscriber admin?')" />
				</form>
			{/if}
		</td>
		<td style="width: 30px">
			<form action="" method="post">
				<div>
				<input type="hidden" name="nren_operation" value="delete_nren_admin" />
				<input type="hidden" name="nren_admin" value="{$admin}" />
		{if ($admin == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('You are about to delete YOURSELF!\nAre you sure?')" />
			</div>
			</form>
			</td>
			<td >{$admin} <span style="cursor:help" title="That's you!">(*)</span></td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('Delete entry {$admin}?')" />
			</div>
			</form>
			</td>
			<td>{$admin}</td>
		{/if}
		</tr>
	{/foreach}
{/if}

<tr>
	<td style="width: 30px">
	</td>
	<td style="width: 30px">
	</td>
	<td>
		<form action="" method="post">
			<div>
			<input type="hidden" name="nren_operation" value="add_nren_admin" />
			<input type="text" name="nren_admin" />
			<input type="submit" name="add" value="Add new" />
			</div>
		</form>
	</td>
</tr>
</table>
</fieldset>


{if !empty($subscribers)}
<div class="spacer"></div>
{* *********************************************************************** *}
{* ***** NREN-admin/subscriber-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
	<legend>
	Admins for subscriber {$subscriber}
	</legend>

	<p class="info">
	Allows you to add/delete Subscriber admins. Subscriber admins may:
	</p>
	<br />
	<ul class="info">
	<li>revoke user certificates</li>
	<li>appoint other subscriber admins.</li>
	</ul>
	<br />
	<p class="info">
	Their scope is limited to an institution, in this case {$subscriber}.
	</p>
	<br />

	{if isset($subscriber_admins)}
		<table>
		<tr>
			<td></td>
			<td></td>
			<td><b>Principal identifier</b></td><td></td>
		</tr>
		{foreach from=$subscriber_admins item=subscriber_admin}
			<tr>
			<td style="width: 30px">
				<form action="" method="post">
				<input type="hidden" name="nren_operation" value="upgrade_subs_admin" />
				<input type="hidden" name="subscriber" value="{$subscriber}" />
				<input type="hidden" name="subs_admin" value="{$subscriber_admin}" />
				<input type="image" src="graphics/arrow_up.png" alt="Upgrade admin"
				name="Upgrade" title="Upgrade admin"
				onclick="return confirm('Upgrade {$subscriber_admin} to a NREN-admin of NREN {$nren}?')" />
				</form>
			</td>
			<td style="width: 30px">
					<form action="" method="post">
					<div>
					<input type="hidden" name="nren_operation" value="delete_subs_admin" />
					<input type="hidden" name="subscriber" value="{$subscriber}" />
					<input type="hidden" name="subs_admin" value="{$subscriber_admin}" />
					<input type="image" src="graphics/delete.png" alt="Delete entry"
					name="delete" onclick="return confirm('Delete entry {$subscriber_admin}?')" />
					</div>
					</form>
			</td><td>{$subscriber_admin}</td>
			</tr>
		{/foreach}

		<tr>
			<td style="width: 30px">
			</td>
			<td style="width: 30px">
			</td>
		<td>
		<form action="" method="post">
		<div>
			<input type="hidden" name="nren_operation" value="add_subs_admin" />
			<input type="hidden" name="subscriber" value="{$subscriber}" />
			<input type="text" name="subs_admin" />
			<input type="submit" name="add" value="Add new" />
		</div>
		</form>
		</td>
		</tr>
		</table>
		<div class="spacer"></div>
		<div style="text-align: right">
			<form action="" method="post">
			<div>
			Select subscriber:
			{html_options name="subscriber" values=$subscribers output=$subscribers selected=$subscriber}
			<input type="submit" name="change" value="Change" />
			</div>
			</form>
		</div>
	{/if}
</fieldset>
{/if}
