<fieldset>
	{* *********************************************************************** *}
	{* ***** subscriber-admin/subscriber-admin view ***** *}
	{* *********************************************************************** *}
	<legend>
 {$l10n_legend_subs_admins2} {$subscriber->getOrgName()|truncate:30:"...":true}
</legend>

	<p class="info">
	{$l10n_infotext_subs_adm4} '{$subscriber->getOrgName()|escape}'.
	{$l10n_infotext_selfmarker} {$l10n_infotext_subs_adm2}
	</p>
	<ul class="info">
	<li>{$l10n_subs_adm_priv1}.</li>
	<li>{$l10n_subs_adm_priv2}.</li>
	</ul>

	{if !empty($subscriber_admins)}
	<table>
	<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px"></td><td style="width: 15em"><b>{$l10n_label_eppn}</b></td>
	<td style="width: 15em"><b>{$l10n_label_adm_name}</b></td>
	</tr>
		<tr>
		<td style="height: 1em"></td>
		</tr>
		{foreach from=$subscriber_admins item=subscriber_admin}
		<tr>
		<td style="width: 30px">
		<form action="" method="post">
			<div>
			<input type="hidden"
			       name="subs_operation"
			       value="downgrade_subs_admin" />
			<input type="hidden"
			       name="subs_admin"
			       value="{$subscriber_admin.eppn}" />
			{$panticsrf}
			{if ($subscriber_admin.eppn == $self)}
			<input type="image" src="graphics/arrow_down.png" alt="{$l10n_title_downgrade_adm}"
			title="{$l10n_title_downgrade_adm}"
			name="Downgrade" onclick="return confirm('{$l10n_confirm_downgrsss1}')" />
			{else}
			<input type="image" src="graphics/arrow_down.png" alt="{$l10n_title_downgrade_adm}"
			title="{$l10n_title_downgrade_adm}"
			name="Downgrade" onclick="return confirm('{$l10n_title_downgrade_adm} {$subscriber_admin.eppn|escape} {$l10n_confirm_downgrsss2}')" />
			{/if}
			</div>
		</form>
		</td>
		<td style="width: 30px">
		<form action="" method="post">
		<div>
		  <input type="hidden"
			 name="subs_operation"
			 value="delete_subs_admin" />
		  <input type="hidden"
			 name="subs_admin"
			 value="{$subscriber_admin.eppn}" />
		  {$panticsrf}
		{if ($subscriber_admin.eppn == $self)}
			<input type="image" src="graphics/delete.png" alt="{$l10n_title_delete_adm}"
				title="{$l10n_title_delete_adm}"
				name="delete" onclick="return confirm('{$l10n_confirm_delete_self}')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin.eppn|escape} <span style="cursor:help" title="{$l10n_title_thatsu}">(*)</span></td>
			<td>{$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</td>
		{else}
			<input type="image" src="graphics/delete.png" alt="{$l10n_title_delete_adm}"
				title="{$l10n_title_delete_adm}"
				name="delete" onclick="return confirm('{$l10n_title_delete_adm} {$subscriber_admin.eppn|escape}?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin.eppn|escape}</td>
			<td>
			{if isset($subscriber_admin.email)}
				<a href="mailto:{$subscriber_admin.email}">{$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</a>
			{else}
				{$subscriber_admin.name|escape|default:"<i>$l10n_info_notassign</i>"}
			{/if}
			</td>
		{/if}
		</tr>
		<tr>
		{* air *}
		<td style="height: 0.5em"></td>
		</tr>
		{/foreach}
		</table>
	{/if}

	<form action="" method="post">
	<table>
	<tr>
	<td style="height: 1em"></td>
	</tr>
	<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px">
	</td>
	<td style="width: 15em">
	  <input type="hidden"
		 name="subs_operation"
		 value="add_subs_admin" />
	  <input type="text" name="subs_admin" />
	  {$panticsrf}

	</td>
	<td style="width: 15em">
		<input type="text" disabled="disabled" value="{$l10n_info_ass_flogin}" />
	</td>
	<td>
		<input type="submit" name="add" value="{$l10n_button_addnew}" />
	</td>
	</tr>
	</table>
	</form>

</fieldset>

<div class="spacer"></div>

{* *********************************************************************** *}
{* ***** subscriber-admin/sub-subscriber-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
<legend>
	{$l10n_legend_subss_admins} {$subscriber->getOrgName()|truncate:30:"...":true}
</legend>

<p class="info">
{$l10n_infotext_subss_adm1} '{$subscriber->getOrgName()|escape}'.
{$l10n_infotext_subss_adm2}
</p>
	<ul class="info">
	<li>{$l10n_subs_adm_priv1}.</li>
	</ul>

{if !empty($subscriber_sub_admins)}
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
{foreach from=$subscriber_sub_admins item=admin}
	<tr>
		<td style="width: 30px">
			<form action="" method="post">
			<div>
			  {$panticsrf}
				<input type="hidden" name="subs_operation" value="upgrade_subs_sub_admin" />
				<input type="hidden" name="subs_sub_admin" value="{$admin.eppn}" />
				<input type="image" src="graphics/arrow_up.png" alt="{$l10n_title_upgrade_adm}"
				title="{$l10n_title_upgrade_adm}" name="upgrade"
				onclick="return confirm('{$l10n_title_upgrade_adm} {$admin.eppn|escape} {$l10n_confirm_upgrsss1} {$subscriber|escape}?')" />
			</div>
			</form>
		</td>
		<td style="width: 30px">
			<form action="" method="post">
			<div>
			  {$panticsrf}
			<input type="hidden" name="subs_operation" value="delete_subs_sub_admin" />
			<input type="hidden" name="subs_sub_admin" value="{$admin.eppn}" />
			<input type="image" src="graphics/delete.png" alt="{$l10n_title_delete_adm}"
			title="{$l10n_title_delete_adm}"
			name="delete" onclick="return confirm('{$l10n_title_delete_adm} {$admin.eppn|escape}?')" />
			</div>
		</form>
		</td>
		<td>{$admin.eppn|escape}</td>
		<td>
			{if isset($admin.email)}
				<a href="mailto:{$admin.email}">{$admin.name|escape|default:"<i>$l10n_info_notassign</i>"}</a>
			{else}
				{$admin.name|escape|default:"<i>$l10n_info_notassign</i>"}
			{/if}
		</td>
	</tr>
	{* air *}
	<tr>
	<td style="height: 0.5em"></td>
	</tr>
{/foreach}
</table>
{/if}

<form method="post" action="">
<table>
<tr>
<td style="height: 1em"></td>
</tr>
<tr>
	<td style="width: 30px"></td>
	<td style="width: 30px"></td>
	<td style="width: 15em">
	  {$panticsrf}
		<input type="hidden" name="subs_operation" value="add_subs_sub_admin" />
		<input type="text" name="subs_sub_admin" />
	</td>
	<td style="width: 15em">
		<input type="text" disabled="disabled" value="{$l10n_info_ass_flogin}" />
	</td>
	<td>
		<input type="submit" name="add" value="{$l10n_button_addnew}" />
	</td>
</tr>
</table>
</form>
</fieldset>

<div class="spacer"></div>
<div class="spacer"></div>
{* ************************************************************************* *}
{* ************** Subscriber-admin/NREN-admin view ***************           *}
{* ************************************************************************* *}
<h3><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> {$l10n_legend_bigboss}</a></h3>
<div class="expcont">
	<div class="spacer"></div>
	<fieldset class="infoblock">
		<legend>{$l10n_legend_nren_admins} {$nren|escape}</legend>
		<p class="info">
		{$l10n_infotext_nren_adm4} {$nren|escape}:
		</p>
		<ul>
		{foreach from=$nren_admins item=nren_admin}
			<li>{$nren_admin.eppn|escape} ({$nren_admin.email|escape|default:"<i>$l10n_info_notassign</i>"})</li>
		{/foreach}
		</ul>
	</fieldset>
</div>
