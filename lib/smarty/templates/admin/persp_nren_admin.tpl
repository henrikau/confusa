{* *********************************************************************** *}
{* ***** NREN-admin/NREN-admin view ***** *}
{* *********************************************************************** *}

<fieldset>
  <legend>NREN admins</legend>

  <p class="info">
    Add and delete NREN admins for your NREN '{$nren|escape}'. You yourself are
    marked with an asterisk (*). NREN admins have many privileges:
  </p>
  <ul class="info">
    <li>Add/delete other NREN admins.</li>
    <li>Add/delete subscriber admins.</li>
    <li>Give institutions within the NREN's domain access to Confusa</li>
    <li>Change the NREN's CA account</li>
    <li>Change the branding of the portal</li>
  </ul>
  <p class="info">
    This role has a large impact on the available
    power. Thus, you should not grant this level to
    everybody, but only to persons who you trust implicitly.
  </p>

  <table>
{if !empty($nren_admins)}
		<tr>
	  <td style="width: 30px"></td><td style="width: 30px"></td><td><b>Principal identifier</b></td>
	   <td><b>Admin name</b></td>
    </tr>
	<tr>
	<td style="height: 1em"></td>
	</tr>
	{foreach from=$nren_admins item=admin}
		<tr>
		<td style="width: 30px">
			{if ($admin.eppn == $self)}
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
				<input type="hidden" name="nren_admin" value="{$admin.eppn}" />
		{if ($admin.eppn == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="Delete admin"
				name="delete" onclick="return confirm('You are about to delete YOURSELF!\nAre you sure?')" />
			</div>
			</form>
			</td>
			<td >{$admin.eppn|escape} <span style="cursor:help" title="That's you!">(*)</span></td>
			<td>{$admin.name|escape}</td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				title="Delete admin"
				name="delete" onclick="return confirm('Delete entry {$admin.eppn|escape}?')" />
			</div>
			</form>
			</td>
			<td style="width: 15em">{$admin.eppn|escape}</td>
			<td style="width: 15em">{$admin.name|escape|default:"<i>not assigned yet</i>"}</td>
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
	<td style="width: 30px">
	</td>
	<td style="width: 30px">
	</td>
	<td style="width: 15em">
		<input type="hidden" name="nren_operation" value="add_nren_admin" />
		<input type="text" name="nren_admin" />
	</td>
	<td style="width: 15em">
		<input type="text" value="Assigned at first login" disabled="disabled" />
	</td>
	<td>
		<input type="submit" name="add" value="Add new" />
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
	<legend>
	Admins for subscriber {$subscriber|escape}
	</legend>

	<p class="info">
	Allows you to add/delete Subscriber admins. Subscriber admins may:
	</p>
	<ul class="info">
	<li>revoke user certificates</li>
	<li>appoint other subscriber admins.</li>
	</ul>
	<p class="info">
	Their scope is limited to an institution, in this case {$subscriber|escape}.
	</p>

	{if isset($subscriber_admins)}
		<table>
		<tr>
			<td></td>
			<td></td>
			<td><b>Principal identifier</b></td>
			<td><b>Admin name</b></td>
		</tr>
		<tr>
		<td style="height: 1em"></td>
		</tr>
		{foreach from=$subscriber_admins item=subscriber_admin}
			<tr>
			<td style="width: 30px">
				<form action="" method="post">
				<input type="hidden" name="nren_operation" value="upgrade_subs_admin" />
				<input type="hidden" name="subscriber" value="{$subscriber}" />
				<input type="hidden" name="subscriberID" value="{$subscriberID}" />
				<input type="hidden" name="subs_admin" value="{$subscriber_admin.eppn}" />
				<input type="image" src="graphics/arrow_up.png" alt="Upgrade admin"
				name="Upgrade" title="Upgrade admin"
				onclick="return confirm('Upgrade {$subscriber_admin.eppn|escape} to a NREN-admin of NREN {$nren|escape}?')" />
				</form>
			</td>
			<td style="width: 30px">
					<form action="" method="post">
					<div>
					<input type="hidden" name="nren_operation" value="delete_subs_admin" />
					<input type="hidden" name="subscriber" value="{$subscriber}" />
					<input type="hidden" name="subscriberID" value="{$subscriberID}" />
					<input type="hidden" name="subs_admin" value="{$subscriber_admin.eppn}" />
					<input type="image" src="graphics/delete.png" alt="Delete entry"
					title="Delete admin"
					name="delete" onclick="return confirm('Delete entry {$subscriber_admin.eppn|escape}?')" />
					</div>
					</form>
			</td><td style="width: 15em">{$subscriber_admin.eppn|escape}</td>
			<td style="width: 15em">{$subscriber_admin.name|escape|default:"<i>not assigned yet</i>"}</td>
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
			<input type="hidden" name="nren_operation" value="add_subs_admin" />
			<input type="hidden" name="subscriber" value="{$subscriber}" />
			<input type="hidden" name="subscriberID" value="{$subscriberID}" />
			<input type="text" name="subs_admin" />
		</td>
		<td style="width: 15em">
			<input type="text" value="Assigned at first login" disabled="disabled" />
		</td>
		<td>
			<input type="submit" name="add" value="Add new" />
		</td>
		</tr>
		</table>
		</form>

		<div class="spacer"></div>
		<div class="spacer"></div>
		<div style="text-align: right">
			<form action="" method="post">
			<div>
			Select subscriber:
			<select name="subscriberID">
			{foreach from=$subscribers item=subscriber}
			{if $subscriber->getDBID() == $subscriberID}
				<option value="{$subscriber->getDBID()|escape}" selected="selected">{$subscriber->getIdPName()|escape}</option>
			{else}
				<option value="{$subscriber->getDBID()|escape}">{$subscriber->getIdPName()|escape}</option>
			{/if}
			{/foreach}
			</select>
			<input type="submit" name="change" value="Change" />
			</div>
			</form>
		</div>
</fieldset>
{/if}
