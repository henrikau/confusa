{if $person->in_admin_mode()}
{if $person->is_nren_admin() ||  $person->is_subscriber_admin()}
	<H3>Add/delete Confusa administrators</H3>
{/if}

{if $person->is_nren_admin()}
<div class="spacer"></div>
{* *********************************************************************** *}
{* ***** NREN-admin/NREN-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
<legend style="cursor:help" title="NREN admins have the same privileges as
 you have. You yourself are highlighted blue in the list. This lets you add/remove
 admins for your NREN '{$nren}'.">
 NREN admins
</legend>

<table>
<tr>
<td style="width: 30px"></td><td><b>Principal identifier</b></td>
</tr>

{if !empty($nren_admins)}
	{foreach from=$nren_admins item=admin}
		<tr>
		<td style="width: 30px">
			<form action="" method="POST">
				<input type="hidden" name="nren_operation" value="delete_nren_admin" />
				<input type="hidden" name="nren_admin" value={$admin} />
				<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('Delete entry {$admin}?')" />
			</form>
		</td>
		{if ($admin == $self)}
			<td style="color: blue"><b>{$admin}</b></td>
		{else}
			<td>{$admin}</td>
		{/if}
		</tr>
	{/foreach}
{/if}

<tr>
	<td style="width: 30px">
	</td>
	<td>
		<form action="" method="POST">
			<input type="hidden" name="nren_operation" value="add_nren_admin" />
			<input type="text" name="nren_admin" />
			<input type="submit" name="add" value="Add new" />
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
		<legend style="cursor:help" title="Subscriber admins can revoke user
 certificates and appoint other subscriber admins.
 Their scope is limited to an institution and they can not edit
 remote-CA account settings.">
		Admins for subscriber {$subscriber}
		</legend>

		{if isset($subscriber_admins)}
			<table>
			<tr>
				<td></td>
				<td><b>Principal identifier</b></td><td></td>
			</tr>
			{foreach from=$subscriber_admins item=subscriber_admin}
				<tr>
				<td style="width: 30px">
						<form action="" method="POST">
						<input type="hidden" name="nren_operation" value="delete_subs_admin" />
						<input type="hidden" name="subscriber" value={$subscriber} />
						<input type="hidden" name="subs_admin" value={$subscriber_admin} />
						<input type="image" src="graphics/delete.png" alt="Delete entry"
						name="delete" onclick="return confirm('Delete entry {$subscriber_admin}?')" />
						</form>
				</td><td>{$subscriber_admin}</td>
				</tr>
			{/foreach}

			<tr>
				<td style="width: 30px">
				</td>
			<td>
			<form action="" method="POST">
				<input type="hidden" name="nren_operation" value="add_subs_admin" />
				<input type="hidden" name="subscriber" value={$subscriber} />
				<input type="text" name="subs_admin" />
				<input type="submit" name="add" value="Add new" />
			</form>
			</td>
			</tr>
			</table>
			<div class="spacer"></div>
			<div style="text-align: right">
				<form action="" method="POST">
				Select subscriber:
				{html_options name="subscriber" values=$subscribers output=$subscribers selected=$subscriber}
				<input type="submit" name="change" value="Change" />
				</form>
			</div>
		{/if}
	</fieldset>


{/if}

{elseif $person->is_subscriber_admin()}
	<div class="spacer"></div>
	<fieldset>
	{* *********************************************************************** *}
	{* ***** subscriber-admin/subscriber-admin view ***** *}
	{* *********************************************************************** *}
	<legend style="cursor:help" title="Administrators of your own organization
 '{$subscriber}'. You yourself are highlighted blue in the list.">
 Subscriber admins
</legend>
	<table>
	<tr>
	<td style="width: 30px"></td><td><b>Principal identifier</b></td>
	</tr>
	{if !empty($subscriber_admins)}
		{foreach from=$subscriber_admins item=subscriber_admin}
		<tr>
		<td style="width: 30px">
		<form action="" method="POST">
				<input type="hidden" name="subs_operation" value="delete_subs_admin" />
				<input type="hidden" name="subs_admin" value={$subscriber_admin} />
				<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('Delete entry {$subscriber_admin}?')" />
		</form>
		</td>
		{if ($subscriber_admin == $self)}
		<td style="color: blue"><b>{$subscriber_admin}</b></td>
		{else}
		<td>{$subscriber_admin}</td>
		{/if}
		</tr>
		{/foreach}
	{/if}

	<tr>
	<td style="width: 30px">
	</td>
	<td>
		<form action="" method="POST">
			<input type="hidden" name="subs_operation" value="add_subs_admin" />
			<input type="text" name="subs_admin" />
			<input type="submit" name="add" value="Add new" />
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
<legend style="cursor:help" title="Subscriber sub-admin are admins of your
 institution that are not allowed to perform any administrative tasks except
 for institution-scoped certificate revocation">
	Subscriber sub-admins
</legend>

<table>

{if !empty($subscriber_sub_admins)}
<tr>
<td style="width: 30px"></td>
<td><b>Principal identifier</b></td>
</tr>
{foreach from=$subscriber_sub_admins item=admin}
	<tr>
		<td style="width: 30px">
			<form action="" method="POST">
			<input type="hidden" name="subs_operation" value="delete_subs_sub_admin" />
			<input type="hidden" name="subs_sub_admin" value={$admin} />
			<input type="image" src="graphics/delete.png" alt="Delete entry"
			name="delete" onclick="return confirm('Delete entry {$admin}?')" />
		</form>
		</td>
		<td>{$admin}</td>
	</tr>
{/foreach}
{/if}

<tr>
	<td style="width: 30px"></td>
	<td>
	<form action="" method="POST">
		<input type="hidden" name="subs_operation" value="add_subs_sub_admin" />
		<input type="test" name="subs_sub_admin" />
		<input type="submit" name="add" value="Add new" />
	</form>
</tr>
</table>
</fieldset>
{/if}

{/if}
