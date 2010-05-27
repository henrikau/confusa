{literal}
<script type="text/javascript">
	function toggleGUIDInfoText(dropdown)
	{
		var selValue = dropdown.options[dropdown.selectedIndex].value;
		var informational = document.getElementById("text_globally_unique");

		if (selValue == "-") {
			informational.style.display='inline';
		} else {
			informational.style.display='none';
		}
	}
</script>
{/literal}

{* *********************************************************************** *}
{* ***** NREN-admin/NREN-admin view ***** *}
{* *********************************************************************** *}

<fieldset>
  <legend>{$l10n_legend_nren_admins} {$nren|escape}</legend>

  <p class="info">
    {$l10n_infotext_nren_adm1} '{$nren|escape}'. {$l10n_infotext_selfmarker} {$l10n_infotext_nren_adm2}
  </p>
  <ul class="info">
    <li>{$l10n_nren_adm_priv1}.</li>
    <li>{$l10n_nren_adm_priv2}.</li>
    <li>{$l10n_nren_adm_priv3}</li>
    <li>{$l10n_nren_adm_priv4}</li>
    <li>{$l10n_nren_adm_priv5}</li>
  </ul>
  <p class="info">
    {$l10n_infotext_nren_adm3}
  </p>

  <table>
{if !empty($nren_admins)}
		<tr>
	  <td style="width: 30px"></td><td style="width: 30px"></td>
	  <td style="width: 15em"><b>{$l10n_label_eppn}</b></td>
	   <td style="width: 15em"><b>{$l10n_label_adm_name}</b></td>
    </tr>
	<tr>
	<td style="height: 1em"></td>
	</tr>
	{foreach from=$nren_admins item=admin}
		<tr>
		<td style="width: 30px">
		  {if ($admin.eppn == $self)}
		  {if $has_adm_entl}
		  <form action ="" method="post">
		    <input type="hidden" name="nren_operation" value="downgrade_self" />
		    <input type="image"
			   src="graphics/arrow_down.png"
			   alt="Downgrade admin"
			   name="Downgrade"
			   title="{$l10n_title_downgrade_adm}"
			   onclick="return confirm('{$l10n_confirm_downgrade_selfn}')" />
		    {$panticsrf}
		  </form>
		  {else}
		  <img src="graphics/flag_orange.png"
		       alt="{$l10n_nren_cannot_downgrade}"
		       title="{$l10n_nren_cannot_downgrade}"
		       />
		  {/if} {* has admin-entitlement *}
		  {/if} {* admin.eppn == self *}
		</td>
		<td style="width: 30px">
			<form action="" method="post">
				<div>
				  {$panticsrf}
				<input type="hidden" name="nren_operation" value="delete_nren_admin" />
				<input type="hidden" name="nren_admin" value="{$admin.eppn}" />
		{if ($admin.eppn == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="{$l10n_title_delete_adm}"
				name="delete" onclick="return confirm('{$l10n_confirm_delete_self}')" />
			</div>
			</form>
			</td>
			<td >{$admin.eppn|escape} <span style="cursor:help" title="{$l10n_title_thatsu}">(*)</span><br />
				<span style="font-size: 0.8em">IdP: {$admin.idp_url|escape|default:'-'}</span>
			</td>
			<td>{$admin.name|escape}</td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="{$l10n_title_delete_adm}"
				name="delete" onclick="return confirm('{$l10n_title_delete_adm} {$admin.eppn|escape}?')" />
			</div>
			</form>
			</td>
			<td style="width: 15em">{$admin.eppn|escape}<br />
				<span style="font-size: 0.8em">IdP: {$admin.idp_url|escape|default:'-'}</span></td>
			<td style="width: 15em">
				{if isset($admin.email)}
					<a href="mailto:{$admin.email}">{$admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</a>
				{else}
					{$admin.name|escape|default:"<i>$l10n_info_notassign</i>"}
				{/if}
			</td>
		{/if}
		</tr>
		{* air *}
		<tr>
		<td style="height: 0.5em"></td>
		</tr>
	{/foreach}
	<tr>
	<td style="height: 1em"></td>
	</tr>
	</table>
{/if}

<form action="" method="post">
<table>
<tr>
	<td colspan="4">
	<p style="font-style: italic; font-size: 0.8em; padding-top: 1em">
		{$l10n_infotext_enteruid}
	</p>
	</td>
</tr>
<tr style="padding-bottom: 0.5em">
	<td style="width: 30px"></td>
	<td style="width: 30px"></td>
	<td style="width: 15em">
		<input type="hidden" name="nren_operation" value="add_nren_admin" />
		<input type="text" name="nren_admin" />
		{$panticsrf}
	</td>
	<td style="width: 15em">
		<input type="text" value="{$l10n_info_ass_flogin}" disabled="disabled" />
	</td>
	<td>
	</td>
</tr>
<tr>
	<td colspan="4">
	<p style="font-style: italic; font-size: 0.8em; padding-top: 1em">
		{$l10n_infotext_idpforadmin}
	</p>
	</td>
</tr>
<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px"><span style="text-align: right">{$l10n_label_idp}</span></td>
	<td colspan="2" style="width: 30em; min-width: 30em">
		<select name="idp" onChange="toggleGUIDInfoText(this)">
		{foreach from=$idps item=idp}
			<option value="{$idp}">{$idp}</option>
		{/foreach}
		</select>
		<span id="text_globally_unique" style="margin-left: 1em; font-size: 0.8em">
			{$l10n_label_uniqueinnren}
		</span>
	</td>
</tr>
<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px"></td>
	<td style="width: 15em"></td>
	<td style="width: 15em"></td>
	<td>
		<input type="submit" name="add" value="{$l10n_button_addnew}" />
	</td>
</tr>
</table>
</form>
</fieldset>


{if !empty($subscribers)}
<div class="spacer"></div>
{* *********************************************************************** *}
{* ***** NREN-admin/subscriber-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
	<legend style="width: 100%; overflow: hidden">
	{$l10n_legend_subs_admins} {$subscriber->getOrgName()|escape|truncate:30:"...":true}
	</legend>

	<p class="info">
	{$l10n_infotext_subs_adm1} {$l10n_infotext_subs_adm2}
	</p>
	<ul class="info">
	<li>{$l10n_subs_adm_priv1}.</li>
	<li>{$l10n_subs_adm_priv2}.</li>
	</ul>
	<p class="info">
	{$l10n_infotext_subs_adm3} {$subscriber->getOrgName()|escape}.
	</p>

	{if isset($subscriber_admins)}
		<table>
		<tr>
			<td style="width: 30px"></td>
			<td style="width: 30px"></td>
			<td style="width: 15em"><b>{$l10n_label_eppn}</b></td>
			<td style="width: 15em"><b>{$l10n_label_adm_name}</b></td>
		</tr>
		<tr>
		<td style="height: 1em"></td>
		</tr>
		{foreach from=$subscriber_admins item=subscriber_admin}
			<tr>
			<td style="width: 30px">
				<form action="" method="post">
				<input type="hidden" name="nren_operation" value="upgrade_subs_admin" />
				<input type="hidden" name="subscriber" value="{$subscriber->getOrgName()}" />
				<input type="hidden" name="subscriberID" value="{$subscriber->getDBID()}" />
				<input type="hidden" name="subs_admin" value="{$subscriber_admin.eppn}" />
				{$panticsrf}
				<input type="image" src="graphics/arrow_up.png" alt="{$l10n_title_upgrade_adm}"
				name="Upgrade" title="{$l10n_title_upgrade_adm}"
				onclick="return confirm('{$l10n_confirm_upgrade_sadm1} {$subscriber_admin.eppn|escape} {$l10n_confirm_upgrade_sadm2} {$nren|escape}?')" />
				</form>
			</td>
			<td style="width: 30px">
					<form action="" method="post">
					<div>
					  {$panticsrf}
					<input type="hidden" name="nren_operation" value="delete_subs_admin" />
					<input type="hidden" name="subscriber" value="{$subscriber->getOrgName()}" />
					<input type="hidden" name="subscriberID" value="{$subscriber->getDBID()}" />
					<input type="hidden" name="subs_admin" value="{$subscriber_admin.eppn}" />
					<input type="image" src="graphics/delete.png" alt="{$l10n_title_delete_adm}"
					title="{$l10n_title_delete_adm}"
					name="delete" onclick="return confirm('{$l10n_title_delete_adm} {$subscriber_admin.eppn|escape}?')" />
					</div>
					</form>
			</td><td style="width: 15em">{$subscriber_admin.eppn|escape}</td>
			<td style="width: 15em">
			{if isset($subscriber_admin.email)}
				<a href="mailto:{$subscriber_admin.email}">{$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</a>
			{else}
				{$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}
			{/if}
			</td>
			</tr>
			<tr>
			<td style="height: 0.5em"></td>
			</tr>
		{/foreach}

		<tr>
		<td style="height: 1em"></td>
		</tr>
		</table>
	{/if}

		<form method="post" action="admin.php">
		<table>
		<tr>
			<td style="width: 30px">
			</td>
			<td style="width: 30px">
			</td>
		<td style="width: 15em">
		  {$panticsrf}
			<input type="hidden" name="nren_operation" value="add_subs_admin" />
			<input type="hidden" name="subscriber" value="{$subscriber->getOrgName()}" />
			<input type="hidden" name="subscriberID" value="{$subscriber->getDBID()}" />
			<input type="text" name="subs_admin" />
		</td>
		<td style="width: 15em">
			<input type="text" value="{$l10n_info_ass_flogin}" disabled="disabled" />
		</td>
		<td>
			<input type="submit" name="add" value="{$l10n_button_addnew}" />
		</td>
		</tr>
		</table>
		</form>

		<div class="spacer"></div>
		<div class="spacer"></div>
		<div style="text-align: right">
			<form action="" method="post">
			<div>
			{$l10n_label_select_subs}:
			<select name="subscriberID">
			{foreach from=$subscribers item=other}
			{if $other->getDBID() == $subscriber->getDBID()}
				<option value="{$other->getDBID()|escape}" selected="selected">{$other->getIdPName()|escape}</option>
			{else}
				<option value="{$other->getDBID()|escape}">{$other->getIdPName()|escape}</option>
			{/if}
			{/foreach}
			</select>
			<input type="submit" name="change" value="{$l10n_button_change}" />
			</div>
			</form>
		</div>
</fieldset>
{/if}
