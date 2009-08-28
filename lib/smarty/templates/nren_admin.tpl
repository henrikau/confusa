
{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN administration</h3>
<br />


{* ---------------------------------------------------------------- *
 *
 *	List and modify subscribers to NREN
 *
 * ---------------------------------------------------------------- *}

<fieldset>
<legend>Subscriber accounts for: {$nrenName}</legend>
<br />
<p class="info">
   Add or change subscriber accounts. A subscriber is an organization
   belonging to the current NREN ({$nrenName}). This is where the status
   of these subscribers can be changed, new added or existing deleted.
</p>
<br />
<p class="info">
	Note: The ID is assigned automatically by Confusa and is the unique identifier
	of the organization.
</p>
<br />
<table>
<tr>
<td style="width: 25px"></td>
<td style="width: 70px"><b>ID</b></td>
<td style="width: 200px"><b>Name</b></td>
<td><b>State</b></td>
<td></td>
</tr>
</table>

{foreach from=$subscriber_list item=row}
		<table>
		<tr>
			{assign value=$nren->format_subscr_on_state($row.org_state) var=style}

			{* Show the delete-subscriber button *}
			<td style="width: 25px">{$nren->delete_button('subscriber', $row.subscriber, $row.subscriber_id)}</td>
			<td style="width: 70px; {$style}">
				{$row.subscriber_id}
			</td>
			<td style="width: 200px; {$style}">
				{$row.subscriber}

				{if $row.subscriber == $self_subscriber}
						<span title="Your own institution" style="cursor:help">(*)</span>
				{/if}
			</td>
			<td>
				<form action="" method="post">
					<div>
					<input type="hidden" name="subscriber" value="edit" />
					<input type="hidden" name="id" value="{$row.subscriber_id}" />
					{$nren->createSelectBox($row.org_state,	null, state)}
					<input type="submit" class="button" value="Update" />
					</div>
				</form>
			</td>
		</tr>
		</table>
{/foreach}

<br />
<form action="" method="post">
<table>
{* Field for adding new subscribers *}
<tr>
	<td style="width: 25px"></td>
	<td style="width: 70px"></td>
	<td style="width: 200px"></td>
	<td></td>
	<td></td>
	<td></td>
</tr>
<tr>
	<td style="witdh: 25px"></td>
	<td style="width: 50px"><input type="hidden" name="subscriber" value="add" /></td>
	<td><input type="text" name="name" /></td>
	<td>{$nren->createSelectBox('', null, 'state')}</td>
	<td> {* air *} </td>
	<td>
		<input type="submit" value="Add new" />
	</td>

</tr>
</table>
</form>
<br />
</fieldset>
<br />
{/if} {* if user is admin *}

<br />
<br />
