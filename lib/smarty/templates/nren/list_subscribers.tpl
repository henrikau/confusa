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
  <br />
  <table>
    <tr>
      <td style="width: 25px"></td>
      <td style="width: 25px"></td>
      <td style="width: 70px"><b>ID</b></td>
      <td style="width: 200px"><b>Name</b></td>
      {if !$subscriber_details}
         <td><b>State</b></td>
      {/if}
      <td></td>
    </tr>
  </table>

  {foreach from=$subscriber_list item=row}
  <table>
    <tr>
      {assign value=$nren->format_subscr_on_state($row.org_state) var=style}

      {* Show the delete-subscriber button *}
      <td style="width: 25px">{$nren->delete_button('subscriber', $row.subscriber, $row.subscriber_id)}</td>

      <td style="width: 25px">{$nren->info_button('subscriber', $row.subscriber, $row.subscriber_id)}</td>

      <td style="width: 70px; {$style}">
	{$row.subscriber_id}
      </td>
      <td style="width: 200px; {$style}">
	{$row.subscriber}

	{if $row.subscriber == $self_subscriber}
	<span title="Your own institution" style="cursor:help">(*)</span>
	{/if}
      </td>
      {if !$subscriber_details || $row.subscriber_id != $subscriber_detail_id}
      <td>
			<form action="" method="post">
			  <div>
				<input type="hidden" name="subscriber" value="editState" />
				<input type="hidden" name="id" value="{$row.subscriber_id}" />
				{$nren->createSelectBox($row.org_state,	null, state)}
				<input type="submit" class="button" value="Update" />
			  </div>
			</form>
      </td>
      {/if}
    </tr>
  </table>

  {* show subscriber info *}
  {if $subscriber_details && $row.subscriber_id == $subscriber_detail_id}
  <div class="spacer"></div>
  <fieldset style="border: 1px dotted #C0C0C0">
    <legend style="border: none; color: #303030">Details for
    {$row.subscriber}</legend>
    <form action="" method="post">
    <table>
      <tr>
	<td style="width: 150px">
		<input type="hidden" name="subscriber" value="edit" />
		<input type="hidden" name="id" value="{$row.subscriber_id}" />
		<input type="hidden" name="dn_name" value="{$row.subscriber}" />
	</td>
	<td><div class="spacer"></div></td>
	<td style="width: 25px"></td>
	<td style="width: 300px"></td>
      </tr>

      <tr>
	<td align="right"></td>
	<td><div class="spacer"> </div></td>
	<td></td>
      </tr>

      <tr>
	<td align="right">Contact phone</td>
	<td align="right" style="width=100px"><div class="spacer"></div></td>
	<td>
	  <input type="text" name="subscr_phone"
	  value="{$subscr_details.subscr_phone}" />
	</td>
      </tr>

      <tr>
	<td align="right">Contact email</td>
	<td align="right" style="width=100px"><div class="spacer"></div></td>
	<td>
	  <input type="text" name="subscr_email"
	  value="{$subscr_details.subscr_email}" />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right">Responsible Person</td>
	<td align="right" style="width=100px"><div class="spacer"></div></td>
	<td>
	  <input type="text" name="subscr_responsible_name" value="{$subscr_details.subscr_resp_name}" />
	</td>
      </tr>

      <tr>
	<td align="right">Responsible Person email</td>
	<td align="right" style="width=100px"><div class="spacer"></div></td>
	<td>
	  <input type="text" name="subscr_responsible_email" value="{$subscr_details.subscr_resp_email}" />
	</td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right">DN: /O=</td>
	<td align="right" style="width=100px"><div class="spacer"></div></td>
	<td><b>{$subscr_details.dn_name}</b></td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right" valign="top">Comments</td>
	<td align="right" style="width=100px"><div class="spacer"></div></td>
	<td>
	  <textarea name="subscr_comment" rows="10" cols="60">{$subscr_details.subscr_comment}</textarea>
	</td>
      </tr>

      <tr>
	<td align="right">State</td>
	<td align="right"
	style="width=100px"><div class="spacer"></div></td>
	<td>{$nren->createSelectBox($row.org_state,	null,
	state)}</td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right"><input type="reset" value="Reset form" /></td>
	<td style="width: 25px"></td>
	<td style="width: 300px"><input type="submit" value="Update {$row.subscriber}" /></td>
      </tr>
    </table>
    </form>
  <br />
   </fieldset>
  <br />
  {/if}

  {/foreach}
  <br />
</fieldset>
