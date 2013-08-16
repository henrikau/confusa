{* ---------------------------------------------------------------- *
*
*	List and modify subscribers to NREN
*
* ---------------------------------------------------------------- *}

<fieldset>
  <legend>{$l10n_legend_listsubs} {$nrenName|escape}</legend>
  <br />
  <p class="info">
    {$l10n_infotext_listsubs1}
  </p>
  <p class="info">
    {$l10n_infotext_listsubs2} <a href="?target=add">"{$l10n_tab_addsubs}"</a>.
  </p>
  <p class="info">
    {$l10n_infotext_listsubs3}</p>
    <table style="padding-bottom: 1em">
      <tr>
	<td><b>Subscribed:</b></td>
	<td style="width: 10px"><div class="spacer"></div></td>
	<td>{$l10n_expl_subscribed}</td>
      </tr>

      <tr>
	<td><b>Unsubscribed:</b></td>
	<td><div class="spacer"></div></td>
	<td>{$l10n_expl_unsubscribed}</td>
      </tr>

      <tr>
	<td><b>Suspended:</b></td>
	<td><div class="spacer"></div></td>
	<td>{$l10n_expl_suspended}</td>
      </tr>
    </table>

  <hr class="table" />
  <br />
  <table>
    <tr>
      <td style="width: 25px"></td>
      <td style="width: 25px"></td>
      <td style="width: 70px"><b>{$l10n_label_id}</b></td>
      <td style="width: 200px"><b>{$l10n_label_name}</b></td>
      {if empty($subscriber_details)}
         <td><b>{$l10n_label_state}</b></td>
      {/if}
      <td></td>
    </tr>
  </table>

  {foreach from=$subscriber_list item=subscriber}
  <table>
      {if $subscriber->getState() == "unsubscribed"}
           <tr style="color: gray; font-weight: bold">
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
			{$panticsrf}

			{if $subscriber->getIdPName() == $self_subscriber}
				<input type="image" name="delete" title="{$l10n_title_deletesubs}"
				       onclick="return confirm('{$l10n_confirm_deleteownsubs} ({$subscriber->getOrgName()})!\n          {$l10n_confirm_delete_confirm}')"
				       value="delete" src="graphics/delete.png"
				       alt="{$l10n_title_deletesubs}" />
			{else}
				<input type="image" name="delete"
				title="{$l10n_title_deletesubs}"
				       onclick="return confirm('{$l10n_confirm_delete_subscriber} {$subscriber->getOrgName()|escape}\n {$l10n_confirm_delete_confirm}')"
				       value="delete" src="graphics/delete.png"
				       alt="{$l10n_title_deletesubs}" />
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
			{$panticsrf}
			<input type="image" name="information" title="{$l10n_title_subsinfo} {$subscriber->getIdPName()|escape}"
			       value="info" src="graphics/information.png"
			       alt="{$l10n_title_subsinfo} {$subscriber->getIdPName()|escape}" />
			</div>
		</form>
	</td>

	  <td style="width: 70px">
		{$subscriber->getDBID()|escape}
	  </td>
	  <td style="width: 200px">
		{$subscriber->getIdPName()|escape}

	{if $subscriber->getIdPName() == $self_subscriber}
	<span title="{$l10n_title_owninst}" style="cursor:help">(*)</span>
	{/if}
      </td>
      {if empty($subscriber_details) || $subscriber->getDBID() != $subscriber_detail_id}
      <td>
			<form action="" method="post">
			  <div>
				<input type="hidden" name="subscriber" value="editState" />
				<input type="hidden" name="id" value="{$subscriber->getDBID()}" />
				{$panticsrf}
				{html_options output=$org_states values=$org_states selected=$subscriber->getState() name=state}
				<input type="submit" class="button"
				value="{$l10n_button_updstate}" />
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
    <legend style="border: none; color: #303030">{$l10n_legend_subsdetails}
    {$subscriber->getIdPName()|escape}</legend>
    <form action="" method="post">
    <table>
      <tr>
	<td style="width: 150px; padding-right: 10px">
		<input type="hidden" name="subscriber" value="edit" />
		<input type="hidden" name="id" value="{$subscriber->getDBID()|escape}" />
		<input type="hidden" name="dn_name" value="{$subscriber->getOrgName()|escape}" />
		{$panticsrf}
	</td>
	<td style="width: 25px"></td>
	<td style="width: 300px"></td>
      </tr>

      <tr><td colspan="3"><b>{$l10n_heading_certinfo}</b></td></tr>
      <tr>
	<td style="font-size: 0.8em; font-style: italic" colspan="2">
	  {$l10n_infotext_dn}
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_dn} /O=</td>
	<td><b>{$subscriber->getOrgName()|escape}</b></td>
	<td></td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

	<tr><td colspan="3"><b>{$l10n_heading_attruid}</b></td></tr>
	<tr>
		<td style="font-size: 0.8em; font-style: italic" colspan="2">
			{$l10n_intotext_attruid_short}
		</td>
	</tr>
	<tr><td><div class="spacer"></div></td><td></td><td></td></tr>
	<tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_uid}</td>
	{assign var='map' value=$subscriber->getMap()}
		<td><b>{$map.eppn|default:$nren_eppn_key|escape}</b></td>
	<td></td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>


      <tr><td colspan="3"><b>{$l10n_heading_contactinfo}</b></td></tr>
      <tr>
	<td style="font-size: 0.8em; font-style: italic" colspan="2">
	  {$l10n_infotext_contactinfo}
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_phone}</td>
	<td>
	  <input type="text" size="40" name="subscr_phone"
	  value="{$subscriber->getPhone()}" />
	</td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_email}</td>
	<td>
	  <input type="text" size="40" name="subscr_email" value="{$subscriber->getEmail()}" />
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>


      <tr><td colspan="3"><b>{$l10n_heading_resppers}</b></td></tr>
      <tr>
	<td style="font-size: 0.8em; font-style: italic" colspan="2">
		{$l10n_infotext_resppers}
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_name}</td>
	<td>
	  <input type="text" size="40" name="subscr_responsible_name" value="{$subscriber->getRespName()}" />
	</td>
      </tr>
      <tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_email}</td>
	<td>
	  <input type="text" size="40" name="subscr_responsible_email" value="{$subscriber->getRespEmail()}" />
	</td>
      </tr>

      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
	<td></td>
      </tr>

      <tr><td colspan="3"><b>{$l10n_heading_helpdesk}</b></td></tr>
       <tr>
	 <td style="font-size: 0.8em; font-style: italic" colspan="2">
	   {$l10n_infotext_helpdesk}
	 </td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td><td></td></tr>
      <tr>
	<td align="right" style="padding-right: 10px">{$l10n_label_url}</td>
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

      <tr><td colspan="3"><b>{$l10n_heading_comments}</b></td></tr>
       <tr>
	 <td style="font-size: 0.8em; font-style: italic" colspan="2">
	   {$l10n_infotext_comments}
	 </td>
      </tr>
      <tr>
	<td colspan="2">
	  <textarea name="subscr_comment" rows="10" cols="60"
	            title="{$l10n_title_comments}">{$subscriber->getComment(true)|escape}</textarea>
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
	<td align="right" style="padding-right: 10px"><input type="reset" value="{$l10n_button_reset}" /></td>
	<td style="width: 300px"><input type="submit" value="{$l10n_button_update} {$subscriber->getIdPName()|escape}" /></td>
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
