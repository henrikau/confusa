{if $person->inAdminMode()}
{if $person->isNRENAdmin() ||  $person->isSubscriberAdmin()}
	<h3>Add/delete Confusa administrators</h3>
{/if}

{if $person->isNRENAdmin()}
<div class="spacer"></div>
{* *********************************************************************** *}
{* ***** NREN-admin/NREN-admin view ***** *}
{* *********************************************************************** *}
<fieldset>
<legend>
 NREN admins
</legend>

<p class="info">
		Add and delete NREN admins for your NREN '{$nren}'. You yourself are
		marked with an asterisk (*). NREN admins have many privileges:
	</p>
	<br />
		<ul class="info">
		<li>Add/delete other NREN admins.</li>
		<li>Add/delete subscriber admins.</li>
		<li>Give institutions within the NREN's domain access to Confusa</li>
		<li>Change the NREN's CA account</li>
		<li>Change the branding of the portal</li>
		</ul>

		<br />
		<p class="info">
		Overall this is a very powerful role and you should think who you want
		to give it.
</p>
<br />

<table>
<tr>
<td style="width: 30px"></td><td><b>Principal identifier</b></td>
</tr>

{if !empty($nren_admins)}

	{foreach from=$nren_admins item=admin}
		<tr>
		<td style="width: 30px">
			<form action="" method="post">
				<div>
				<input type="hidden" name="nren_operation" value="delete_nren_admin" />
				<input type="hidden" name="nren_admin" value="{$admin}" />
		{if ($admin == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('You are about to delete YOURSELF!\nAre you sure?')" />
			</div>
			</form>
			</td>
			<td >{$admin} <span style="cursor:help" title="That's you!">(*)</span></td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('Delete entry {$admin}?')" />
			</div>
			</form>
			</td>
			<td>{$admin}</td>
		{/if}
		</tr>
	{/foreach}
{/if}

<tr>
	<td style="width: 30px">
	</td>
	<td>
		<form action="" method="post">
			<div>
			<input type="hidden" name="nren_operation" value="add_nren_admin" />
			<input type="text" name="nren_admin" />
			<input type="submit" name="add" value="Add new" />
			</div>
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
		<legend>
		Admins for subscriber {$subscriber}
		</legend>

		<p class="info">
		Allows you to add/delete Subscriber admins. Subscriber admins may:
		</p>
		<br />
		<ul class="info">
		<li>revoke user certificates</li>
		<li>appoint other subscriber admins.</li>
		</ul>
		<br />
		<p class="info">
		Their scope is limited to an institution, in this case {$subscriber}.
		</p>
		<br />

		{if isset($subscriber_admins)}
			<table>
			<tr>
				<td></td>
				<td><b>Principal identifier</b></td><td></td>
			</tr>
			{foreach from=$subscriber_admins item=subscriber_admin}
				<tr>
				<td style="width: 30px">
						<form action="" method="post">
						<div>
						<input type="hidden" name="nren_operation" value="delete_subs_admin" />
						<input type="hidden" name="subscriber" value="{$subscriber}" />
						<input type="hidden" name="subs_admin" value="{$subscriber_admin}" />
						<input type="image" src="graphics/delete.png" alt="Delete entry"
						name="delete" onclick="return confirm('Delete entry {$subscriber_admin}?')" />
						</div>
						</form>
				</td><td>{$subscriber_admin}</td>
				</tr>
			{/foreach}

			<tr>
				<td style="width: 30px">
				</td>
			<td>
			<form action="" method="post">
			<div>
				<input type="hidden" name="nren_operation" value="add_subs_admin" />
				<input type="hidden" name="subscriber" value="{$subscriber}" />
				<input type="text" name="subs_admin" />
				<input type="submit" name="add" value="Add new" />
			</div>
			</form>
			</td>
			</tr>
			</table>
			<div class="spacer"></div>
			<div style="text-align: right">
				<form action="" method="post">
				<div>
				Select subscriber:
				{html_options name="subscriber" values=$subscribers output=$subscribers selected=$subscriber}
				<input type="submit" name="change" value="Change" />
				</div>
				</form>
			</div>
		{/if}
	</fieldset>


{/if}

{elseif $person->isSubscriberAdmin()}
	<div class="spacer"></div>
	<fieldset>
	{* *********************************************************************** *}
	{* ***** subscriber-admin/subscriber-admin view ***** *}
	{* *********************************************************************** *}
	<legend>
 Subscriber admins
</legend>

	<p class="info">
	Add/delete administrators for your institution '{$subscriber}'.
	You yourself are marked with an asterisk (*). Subscriber admins have the
	following privileges:
	</p>
	<br />
	<ul class="info">
	<li>Revoke certificates of users of their own institution</li>
	<li>Add/delete other subscriber admins and subscriber-subadmins</li>
	</ul>
	<br />

	<table>
	<tr>
	<td style="width: 30px"></td><td><b>Principal identifier</b></td>
	</tr>
	{if !empty($subscriber_admins)}
		{foreach from=$subscriber_admins item=subscriber_admin}
		<tr>
		<td style="width: 30px">
		<form action="" method="post">
		<div>
				<input type="hidden" name="subs_operation" value="delete_subs_admin" />
				<input type="hidden" name="subs_admin" value="{$subscriber_admin}" />
		{if ($subscriber_admin == $self)}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('You are about to delete YOURSELF!\nAre you sure?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin} <span style="cursor:help" title="That's you!">(*)</span></td>
		{else}
			<input type="image" src="graphics/delete.png" alt="Delete entry"
				name="delete" onclick="return confirm('Delete entry {$subscriber_admin}?')" />
			</div>
			</form>
			</td>
			<td>{$subscriber_admin}</td>
		{/if}
		</tr>
		{/foreach}
	{/if}

	<tr>
	<td style="width: 30px">
	</td>
	<td>
		<form action="" method="post">
		<div>
			<input type="hidden" name="subs_operation" value="add_subs_admin" />
			<input type="text" name="subs_admin" />
			<input type="submit" name="add" value="Add new" />
		</div>
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
<legend>
	Subscriber sub-admins
</legend>

<p class="info">
Add/delete subscriber-subadmins for your institution '{$subscriber}'.
Subscriber sub-admins have the following privileges:
</p>
<br />
	<ul class="info">
	<li>Revoke certificates of users of their own institution</li>
	</ul>
<br />

<table>

{if !empty($subscriber_sub_admins)}
<tr>
<td style="width: 30px"></td>
<td><b>Principal identifier</b></td>
</tr>
{foreach from=$subscriber_sub_admins item=admin}
	<tr>
		<td style="width: 30px">
			<form action="" method="post">
			<div>
			<input type="hidden" name="subs_operation" value="delete_subs_sub_admin" />
			<input type="hidden" name="subs_sub_admin" value="{$admin}" />
			<input type="image" src="graphics/delete.png" alt="Delete entry"
			name="delete" onclick="return confirm('Delete entry {$admin}?')" />
			</div>
		</form>
		</td>
		<td>{$admin}</td>
	</tr>
{/foreach}
{/if}

<tr>
	<td style="width: 30px"></td>
	<td>
	<form action="" method="post">
	<div>
		<input type="hidden" name="subs_operation" value="add_subs_sub_admin" />
		<input type="text" name="subs_sub_admin" />
		<input type="submit" name="add" value="Add new" />
	</div>
	</form>
	</td>
</tr>
</table>
</fieldset>

<div class="spacer"></div>
<div class="spacer"></div>
{* ************************************************************************* *}
{* ************** Subscriber-admin/NREN-admin view ***************           *}
{* ************************************************************************* *}
<h3><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> Your super administrators</a></h3>
<div class="expcont">
	<div class="spacer"></div>
	<fieldset class="infoblock">
		<legend>Admins for your NREN {$nren}</legend>
		<p class="info">
		NREN-admins are administrating the whole NREN admin domain, i.e. your institution
		along with other institutions. They can also define, which institutions are
		hooked up to Confusa and which credentials should be used for communicating
		with the Online-CA. Below you can find a list of them for your NREN {$nren}:
		</p>
		<br />
		<ul>
		{foreach from=$nren_admins item=nren_admin}
			<li>{$nren_admin}</li>
		{/foreach}
		</ul>
	</fieldset>
</div>

{elseif $person->isSubscriberSubAdmin()}
<h3><a href="javascript:void(0)" onclick="toggleExpand(this)"><span class="expchar">+</span> Your super administrators</a></h3>
<div class="expcont">
	<div class="spacer"></div>
	<fieldset class="infoblock">
		<legend>Admins for your institution {$subscriber}</legend>
		<p class="info">
		The following are the subscriber admins that are administrating your institution:
		</p>
		<br />
		<ul>
		{foreach from=$subscriber_admins item=subscriber_admin}
			<li>{$subscriber_admin}</li>
		{/foreach}
		</ul>
	</fieldset>
	<div class="spacer"></div>
	<div class="spacer"></div>
</div>
{* Show infoblock for subscriber sub-admins only if they include any other admins but the admin herself *}
{if empty($subscriber_sub_admins) === FALSE}
	<h3><a href="javascript:void(0)" onclick="toggleExpand(this)"><span class="expchar">+</span> Your fellow administrators</a></h3>
	<div class="expcont">
		<div class="spacer"></div>
		<fieldset class="infoblock">
			<legend>Subadmins for your institution {$subscriber}</legend>
			<p class="info">
			The following are sub-admins for your insitutions, who, like you, may revoke
			certificates:
			</p>
			<br />
			<ul>
			{foreach from=$subscriber_sub_admins item=sub_admin}
				<li>{$sub_admin}</li>
			{/foreach}
			</ul>
		</fieldset>
	</div>
{/if}
{/if}

{/if}
