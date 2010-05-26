<script type="text/javascript">
{literal}
function toggleUidAttrField()
{
	if (uidAttrField.hasAttribute("disabled")) {
		uidAttrField.removeAttribute("disabled");
	} else {
		uidAttrField.setAttribute("disabled", "disabled");
	}
}
{/literal}
</script>

<fieldset>
  <legend>{$l10n_legend_addnew}</legend>
  <form action="" method="post">
    <p>
    <input type="hidden" name="subscriber" value="add" />
    {$panticsrf}
    </p>
    <table width="90%">
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    {$l10n_infotext_addnew1}
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>


      {* ------------------------------------------------------ *}
      {* Attribute Name (db_name)				*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr>
	<td colspan="2">
	  <h3>{$l10n_heading_attnm}</h3>
	</td>
      </tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    {$l10n_infotext_attnm1}
	  </p>
	  <p class="info">
	    {$l10n_infotext_attnm2}
	  </p>
		{if isset($nrenOrgAttr)}
			<strong>{$nrenOrgAttr|escape}</strong><br />
		{else}
			<span style="color: #ff0000">{$l10n_label_undefined|escape}</span>
		{/if}
	  <br />
	  <p class="info">
	    {$l10n_infotext_attnm3}
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td class="attr">{$l10n_label_attnm}</td>
	<td>
	  <input type="text" name="db_name" size="40" />
	</td>
      </tr>
      {if isset($foundUniqueName)}
		<tr><td></td><td style="font-size: 0.8em; font-style: italic">{$l10n_label_forinstance} {$foundUniqueName|escape}</td></tr>
	{/if}
	  <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>


      {* ------------------------------------------------------ *}
      {* Org-Name						*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>{$l10n_heading_dnoname}</h3></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    {$l10n_infotext_dnoname1}
	  </p>
	  {if $confusa_grid_restrictions === TRUE}
	  <br />
	  <p class="info">
	    {$l10n_infotext_gridmode}
	  </p>
	  {/if}
	</td>
	</tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td class="attr">/O=</td>

	{if $confusa_grid_restrictions === TRUE}
	<td><input maxlength="62" type="text" name="dn_name" size="40"/></td>
	{else}
	<td><input type="text" name="dn_name" size="40"/></td>
	{/if}
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>

	  {* ------------------------------------------------------ *}
	  {* Unique identifier attribute of that subscriber			*}
	  {* ------------------------------------------------------ *}
	<tr>
		<td colspan="2"><h3>{$l10n_heading_attruid}</h3></td>
	</tr>
	<tr>
		<td colspan="2">
		<p class="info">
			{$l10n_infotext_attruid}
		</p>
		</td>
	</tr>
	<tr><td><div class="spacer"></div></td><td></td></tr>
	<tr>
	<td class="attr">Attribute-key:</td>
	<td>
	  <input id="uid_attr_field"
		 type="text"
		 name="uid_attr"
		 size="40"
		 value="{$eppnAttr}" />
	</td>
	</tr>
	<tr><td>&nbsp;</td><td>
		<input id="uid_attr_box" type="checkbox" name="inherit_uid_attr" value="Inherit" checked="checked" onChange="toggleUidAttrField();" />
		<label for="uid_attr_box">Inherit from NREN mapping</label>
	</td>
	</tr>
	<tr><td><div class="spacer"></div></td><td>&nbsp;</td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>

      {* ------------------------------------------------------ *}
      {* Contact information for subscriber			*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>{$l10n_heading_contactinfo}</h3></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    {$l10n_infotext_contactinfo2}
	  </p>
	  <p class="info">
	    {$l10n_infotext_contactinfo3}
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td class="attr">{$l10n_label_contactemail}</td>
	<td><input type="text" name="subscr_email" size="40"/></td>
      </tr>
      <tr><td></td><td>
      <span style="font-size: 0.8em; font-style: italic">
	{$l10n_expl_contactemail}
      </span>
	</td>
      </tr>
      <tr>
	<td class="attr">{$l10n_label_contactphone}</td>
	<td><input type="text"
		   name="subscr_phone"
		   size="40" />
	</td>
      </tr>
      <tr><td></td><td>
      <span style="font-size: 0.8em; font-style: italic">
	{$l10n_expl_contactphone}
      </span>
      </td>
      </tr>
      <tr>
	<td class="attr">{$l10n_heading_resppers}:</td>
	<td><input type="text" name="subscr_responsible_name" size="40" /></td>
      </tr>
      <tr>
      <td></td>
      <td style="font-size: 0.8em; font-style: italic">
	{$l10n_expl_resppers}
      </td>
      </tr>
      <tr>
	<td class="attr">{$l10n_label_respemail}</td>
	<td><input type="text" name="subscr_responsible_email" size="40" /></td>
      </tr>
      <tr>
      <td></td><td>
      <span style="font-size: 0.8em; font-style: italic">
        {$l10n_expl_respemail}
      </span>
      </td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>



      {* ------------------------------------------------------ *}
      {* Helpdesk information					*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>{$l10n_heading_helpdeskcont}</h3></td></tr>
      <tr><td><div class="spacer" /></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	   {$l10n_infotext_helpdeskcont}
	  </p>
	</td>
	</tr>
      <tr><td><div class="spacer" /></td><td></td></tr>
      <tr>
	<td class="attr">{$l10n_label_helpdeskurl}</td>
	<td><input type="text" name="subscr_help_url" size="40"/></td>
      </tr>
      <tr><td><div class="spacer" /></td><td></td></tr>
      <tr>
	<td class="attr">{$l10n_label_helpdeskemail}</td>
	<td><input type="text" name="subscr_help_email" size="40" /></td>
      </tr>


      <tr><td><div class="spacer" /></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer" /></td><td></td></tr>

      {* ------------------------------------------------------ *}
      {* Comment						*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>{$l10n_heading_arbcomm}</h3></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    {$l10n_infotext_arbcomm1}
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <textarea name="subscr_comment" rows="10" cols="60"></textarea>
	  </td>
      </tr>

      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>

      {* ------------------------------------------------------ *}
      {* Subscriber state					*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>{$l10n_heading_subscrstate}</h3></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    {$l10n_infotext_subscrstate}
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <dl style="padding-bottom: 1em">
	    <dt style="text-decoration: underline; font-style: italic">Subscribed:</dt>
	    <dd class="info">
		{$l10n_infotext_subscribed}
	    </dd>
	    <dt style="text-decoration: underline; font-style: italic">Unsubscribed:</dt>
	    <dd class="info">
		{$l10n_infotext_unsubscribed}
	    </dd>
	    <dt style="text-decoration: underline; font-style: italic">Suspended:</dt>
	    <dd>
		{$l10n_infotext_suspended}
		</dd>

	  </dl>
	</td>
      </tr>
      <tr>
	<td class="attr">{$l10n_heading_subscrstate}:</td>
	<td>{html_options output=$org_states values=$org_states selected="unsubscribed" name=state}</td>
      </tr>

      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    {$l10n_infotext_revise}
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td><input type="reset" value="{$l10n_button_reset}" /></td>
	<td><input type="submit" value="{$l10n_button_addsubs}" /></td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>

<script type="text/javascript">
	var uidAttrField = document.getElementById('uid_attr_field');
	uidAttrField.setAttribute("disabled", "disabled");
</script>
