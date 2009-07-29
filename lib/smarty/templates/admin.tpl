{if $person->in_admin_mode()}
{if $person->is_nren_admin() ||  $person->is_subscriber_admin()}
<H3>Add/modify/delete Confusa administrators</H3>
{/if}

{if $person->is_nren_admin()}
<div class="spacer"></div>
{if !empty($nren_admins)}
	<fieldset>
	<legend>NREN admins</legend>

	<table>
	<td style="width: 20px"></td><td><b>eduPersonPrinipalName</b></td><td></td>
	{foreach from=$nren_admins item=admin}
		<tr>
		<td style="width: 30px">
			<form action="" method="POST">
				<input type="hidden" name="privilege" value="nren" />
				<input type="hidden" name="operation" value="delete" />
				<input type="hidden" name="admin" value="{$admin}" />
				<input type="image" src="graphics/delete.png" alt="Delete enry" name="delete" onclick="return confirm('Delete entry {$admin}?')" />
			</form>
		</td>
		<td>{$admin}</td>
		</tr>
	{/foreach}

	<tr>
		<td style="width: 30px">
		</td>
		<td>
			<form action="" method="POST">
				<input type="hidden" name="privilege" value="nren" />
				<input type="hidden" name="operation" value="add" />
				<input type="hidden" name="nren" value={$nren} />
				<input type="text" name="ePPN" />
				<input type="submit" name="add" value="Add new" />
			</form>
		</td>
	</tr>
	</table>
	</fieldset>
{/if}
{/if}
{/if}
