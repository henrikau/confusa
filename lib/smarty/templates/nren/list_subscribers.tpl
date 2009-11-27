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
    A subscriber can be in one of three states:</p>
    <table style="padding-bottom: 1em">
      <tr>
	<td><b>Subscribed:</b></td>
	<td style="width: 10px"><div class="spacer"></div></td>
	<td>Users can get certificates from the portal</td>
      </tr>

      <tr>
	<td><b>Unsubscribed:</b></td>
	<td><div class="spacer"></div></td>
	<td>Not yet ready to issue certificates to theusers.</td>
      </tr>

      <tr>
	<td><b>Suspended:</b></td>
	<td><div class="spacer"></div></td>
	<td>Users may not issue new certificates.</td>
      </tr>
    </table>

  <hr class="table" />
  <br />
  <table>
    <tr>
      <td style="width: 25px"></td>
      <td style="width: 25px"></td>
      <td style="width: 70px"><b>ID</b></td>
      <td style="width: 200px"><b>Name</b></td>
      {if empty($subscriber_details)}
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
      {if empty($subscriber_details) || $subscriber->getDBID() != $subscriber_detail_id}
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
  {if isset($subscriber_details) && $subscriber->getDBID() == $subscriber_detail_id}
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

      <tr><td colspan="3"><b>Certificate Information:</b></td></tr>
      <tr>
	<td style="font-size: 0.8em; font-style: italic" colspan="2">
	  The name that goes into the certificate <b>must</b> be a
	  stable value. If this needs to change, the subscriber must
	  be <b>removed</b> and created anew with the new name.
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
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


      <tr><td colspan="3"><b>Contact information</b></td></tr>
      <tr>
	<td style="font-size: 0.8em; font-style: italic" colspan="2">
	  Official contact. This is where the policy-makers
	  reside. Whenever a contract needs signing etc.
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">Phone</td>
	<td>
	  <input type="text" size="40" name="subscr_phone"
	  value="{$subscriber->getPhone()}" />
	</td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">E-mail</td>
	<td>
	  <input type="text" size="40" name="subscr_email" value="{$subscriber->getEmail()}" />
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>


      <tr><td colspan="3"><b>Responsible person</b></td></tr>
      <tr>
	<td style="font-size: 0.8em; font-style: italic" colspan="2">
	  Technical contact - not enduser support. Whenever there are
	  issues with attributes etc.
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">Name</td>
	<td>
	  <input type="text" size="40" name="subscr_responsible_name" value="{$subscriber->getRespName()}" />
	</td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">E-mail</td>
	<td>
	  <input type="text" size="40" name="subscr_responsible_email" value="{$subscriber->getRespEmail()}" />
	</td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr><td colspan="3"><b>Help desk information</b></td></tr>
       <tr>
	 <td style="font-size: 0.8em; font-style: italic" colspan="2">
	   End-user support. Where you want the portal to send general
	   inquiries (support questions).
	 </td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">URL</td>
	<td>
	  <input type="text" size="40" name="subscr_help_url" value="{$subscr_details.subscr_help_url}" />
	</td>
	<td></td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">E-mail</td>
	<td>
	  <input type="text" size="40" name="subscr_help_email" value="{$subscr_details.subscr_help_email}" />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr><td colspan="3"><b>Comments</b></td></tr>
       <tr>
	 <td style="font-size: 0.8em; font-style: italic" colspan="2">
	   Comments regarding the subscriber that cannot fit into the
	   pre-defined fields. Note that this field will never be
	   exposed to the subscriber, so you cannot use this to send
	   in-portal messages to the Subscriber's administrators.
	 </td>
      </tr>
      <tr>
	<td colspan="2">
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
