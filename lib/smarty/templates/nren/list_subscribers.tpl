{* ---------------------------------------------------------------- *
*
*	List and modify subscribers to NREN
*
* ---------------------------------------------------------------- *}

<fieldset>
  <legend>Subscriber accounts for: {$nrenName|escape}</legend>
  <br />
  <p class="info">
    This is where you manage subscriber accounts. A subscriber in this
    context is typically one of your customers that has subscribed to
    your TCS eScience Personal service.
    {*
    FIXME:
    what about person cert. portal, should not mention "eScience" here
    then.
    *}
  </p>
  <p class="info">
    You can <b>modify</b> and <b>delete</b> subscribers here. If you
    want to add new subscriber, you can do this
    under <a href="?target=add">"Add new"</a>.
  </p>
  <p class="info">
    A subscriber can be in one of three states:
    <table>
      <tr>
	<td><b>Subscribed:</b></td>
	<td width="10px"><div class="spacer"></div>
	<td>Users can get certificates from the portal</td>
      </tr>

      <tr>
	<td><b>Unsubscribed:</b></td>
	<td><div class="spacer"></div>
	<td>Not yet ready to issue certificates to theusers.</td>
      </tr>

      <tr>
	<td><b>Suspended:</b></td>
	<td><div class="spacer"></div>
	<td>Users may not issue new certificates.</td>
      </tr>
    </table>
  <br />
  </p>

  <hr class="table" />
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

  {foreach from=$subscriber_list item=subscriber}
  <table>
      {if $subscriber->getState() == "unsubscribed"}
           <tr style="color: gray; font-weight: bold">
			{$subscriber->getDBID()|escape}
	  {elseif $subscriber->getState() == "suspended"}
			<tr style="color: red; font-weight: bold">
	  {elseif $subscriber->getState() == "subscribed"}
			<tr style="font-style: italic">
	  {/if}

	    {* Show the delete-subscriber button *}
      <td style="width: 25px">
		<form action="" method="post">
		<div>
			<input type="hidden" name="subscriber" value="delete" />
			<input type="hidden" name="name" value="{$subscriber->getIdPName()|escape}" />
			<input type="hidden" name="id" value="{$subscriber->getDBID()|escape}" />

			{if $subscriber->getIdPName() == $self_subscriber}
				<input type="image" name="delete" title="Delete"
				       onclick="return confirm('You are about to delete your OWN INSTITUTION ({$subscriber->getOrgName()})!\n          Are you sure about that?')"
				       value="delete" src="graphics/delete.png"
				       alt="delete" />
			{else}
				<input type="image" name="delete" title="Delete"
				       value="delete" src="graphics/delete.png"
				       alt="delete" />
			{/if}
		</div>
		</form>
     </td>

      <td style="width: 25px">
		<form action="" method="post">
			<div>
			<input type="hidden" name="subscriber" value="info" />
			<input type="hidden" name="name" value="{$subscriber->getIdPName()|escape}" />
			<input type="hidden" name="id" value="{$subscriber->getDBID()|escape}" />
			<input type="image" name="information" title="Information"
			       value="info" src="graphics/information.png"
			       alt="Information about {$subscriber->getIdPName()|escape}" />
			</div>
		</form>
	</td>

	  <td style="width: 70px">
		{$subscriber->getDBID()|escape}
	  </td>
	  <td style="width: 200px">
		{$subscriber->getIdPName()|escape}

	{if $subscriber->getIdPName() == $self_subscriber}
	<span title="Your own institution" style="cursor:help">(*)</span>
	{/if}
      </td>
      {if !$subscriber_details || $subscriber->getDBID() != $subscriber_detail_id}
      <td>
			<form action="" method="post">
			  <div>
				<input type="hidden" name="subscriber" value="editState" />
				<input type="hidden" name="id" value="{$subscriber->getDBID()}" />
				{html_options output=$org_states values=$org_states selected=$subscriber->getState() name=state}
				<input type="submit" class="button"
				value="Update state" />
			  </div>
			</form>
      </td>
      {/if}
    </tr>
  </table>

  {* show subscriber info *}
  {if $subscriber_details && $subscriber->getDBID() == $subscriber_detail_id}
  <div class="spacer"></div>
  <fieldset style="border: 1px dotted #C0C0C0">
    <legend style="border: none; color: #303030">Details for
    {$subscriber->getIdPName()|escape}</legend>
    <form action="" method="post">
    <table>
      <tr>
	<td style="width: 150px; padding-right: 10px">
		<input type="hidden" name="subscriber" value="edit" />
		<input type="hidden" name="id" value="{$subscriber->getDBID()|escape}" />
		<input type="hidden" name="dn_name" value="{$subscriber->getOrgName()|escape}" />
	</td>
	<td style="width: 25px"></td>
	<td style="width: 300px"></td>
      </tr>

      <tr>
	<td align="right"></td>
	<td></td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">Contact phone</td>
	<td>
	  <input type="text" name="subscr_phone"
	  value="{$subscriber->getPhone()}" />
	</td>
      </tr>
      <tr>
		<td></td>
		<td style="font-size: 0.8em; font-style: italic">
			e.g. the support teams's phone number
		</td>
      </tr>

      <tr>
	<td align="right" style="padding-right: 10px">Contact email</td>
	<td>
	  <input type="text" name="subscr_email" value="{$subscriber->getEmail()}" />
	</td>
      </tr>
       <tr>
		<td></td>
		<td style="font-size: 0.8em; font-style: italic">
			e.g. the support teams's e-mail address
		</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right" style="padding-right: 10px">Responsible Person</td>
	<td>
	  <input type="text" name="subscr_responsible_name" value="{$subscriber->getRespName()}" />
	</td>
      </tr>
       <tr>
		<td></td>
		<td style="font-size: 0.8em; font-style: italic">
			technical contact - not enduser support
		</td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">Responsible Person email</td>
	<td>
	  <input type="text" name="subscr_responsible_email" value="{$subscriber->getRespEmail()}" />
	</td>
      </tr>

       <tr>
		<td></td>
		<td style="font-size: 0.8em; font-style: italic">
			technical contact's mail address
		</td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right" style="padding-right: 10px">DN: /O=</td>
	<td><b>{$subscriber->getOrgName()|escape}</b></td>
	<td></td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right" valign="top" style="padding-right: 10px">Comments</td>
	<td>
	  <textarea name="subscr_comment" rows="10" cols="60"
	            title="Arbitrary comment about the subscriber">{$subscriber->getComment()|escape}</textarea>
	</td>
      </tr>

      <tr>
	<td align="right" style="padding-right: 10px">State</td>
	<td>{html_options output=$org_states values=$org_states selected=$subscriber->getState() name=state}</td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr>
	<td align="right" style="padding-right: 10px"><input type="reset" value="Reset form" /></td>
	<td style="width: 300px"><input type="submit" value="Update {$subscriber->getIdPName()|escape}" /></td>
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
