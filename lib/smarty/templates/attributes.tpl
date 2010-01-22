{if $person->isNRENAdmin() || $person->isSubscriberAdmin()}
{literal}
<script type="text/javascript">
  <!-- Ask the stylist for the value of the attribute key	-->
  <!-- currently selected in 'selectElement' with a polite 	-->
  <!-- asynchronous GET message					-->
  <!--  -->
  <!-- @param	selectElement DOM-Node	The <select> containing	-->
  <!--					the selected attribute	-->
  <!--					key			-->
  <!-- @param	targetElementID string	The ID of the element	-->
  <!--					where the value should	-->
  <!--					be written to		-->
  <!-- @param	errMsg string: A string that will be displayed if the -->
  <!--						attribute value can not be fetched -->
  <!-- @return	void						-->
	function fetchAttributeValue(selectElement, targetElementID, errMsg)
	{
		var req = new XMLHttpRequest();
		var field = document.getElementById(targetElementID);

		req.open("GET", "?attr_value=" + selectElement.value, true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4 /*complete*/) {
				if (req.status == 200) {
					if (req.responseText.length > 0) {
						field.innerHTML = req.responseText;
						field.title = req.responseText;
					}
				} else {
					field.innerHTML = errMsg;
				}
			}
		}
	}
</script>
{/literal}
{* avoid problems with hidden overflow and notification messages *}
<div class="spacer"></div>
<fieldset>
{if $person->isNRENAdmin()}
  <legend>{$l10n_legend_nren_attributes}</legend>
  <p class="info">
    <br />
    {$l10n_infotext_nren_attr1} ({$person->getNREN()|escape}). {$l10n_infotext_nren_attr2}
  </p>
  <p class="info">
    <br />
    {$l10n_infotext_nren_attr3}
  </p>
{elseif $person->isSubscriberAdmin()}
	<legend>{$l10n_legend_subs_attributes}</legend>
	<p class="info">
		<br />
		{$l10n_infotext_subs_attr1}
		'{$subscriber->getIdPName()|escape}'.
		{$l10n_infotext_subs_attr2} '{$person->getNREN()|escape}'
		{$l10n_infotext_subs_attr3}
	</p>
	<p class="info">
		<br />
		{$l10n_infotext_subs_attr4}
	</p>
{/if}

  <br />
  <br />

  <form action="" method="post">
    <table class="mapping" width="95%" border="1" rules="none" cellpadding="5" cellspacing="5">
      <tr>
	<th align="left">{$l10n_th_category}</th>
	<th align="center">{$l10n_th_curkey}</th>
	<th align="left">{$l10n_th_value}</th>
      </tr>
      <tr>
	<td align="right">{$l10n_label_country}</td>
	<td align="center"><b><span style="color: darkgray">-</span></b></td>
	<td>{$nren->getCountry()|escape}
	<input type="hidden" name="attributes_operation" value="update_map" />
	</td>
      </tr>


      {*
      * Global, unique and traceable id 'eppn' aka
      * eduPersonPrincipalName
      *}
      <tr>
	<td align="right">{$l10n_label_eppn}</td>
	<td align="center"><span style="color: darkgray">{$person->getEPPNKey()|escape}</span></td>
	<td>{$person->getEPPN()|escape|default:"<span style=\"color: red\">$l10n_value_undefined</span>"}</td>
      </tr>

      {*
       * Organization 'epodn'
       *}
      <tr>
	<td align="right">{$l10n_label_epodn}<br /></td>
	<td align="right">
	  <select {if ! $person->isNRENAdmin()} disabled="disabled"{/if} name="epodn" onchange="fetchAttributeValue(this, 'orgNameField', '{$l10n_err_attvalna}');">
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.epodn}selected="selected"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="orgNameField" class="attval" title="{$epodn|escape}">{$epodn|escape|default:"<span style=\"color: red\">$l10n_value_undefined</span>"}</td>
      </tr>


      {*
       * Full Name 'cn'
       *}
      <tr>
	<td align="right">{$l10n_label_cn}<br /></td>
	<td align="right">
	  <select name="cn" onchange="fetchAttributeValue(this, 'cnField', '{$l10n_err_attvalna}');">
	    <option value="&nbsp;">&nbsp; </option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.cn}selected="selected"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="cnField" class="attval" title="{$cn|escape}">{$cn|escape|default:"<span style=\"color: red\">$l10n_value_undefined</span>"}</td>
      </tr>

      {*
       * mail
       *}
      <tr>
	<td align="right">{$l10n_label_mail}<br /></td>
	<td align="right">
	  <select name="mail" onchange="fetchAttributeValue(this, 'emailField', '{$l10n_err_attvalna}');">
	    <option value="&nbsp;">&nbsp; </option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.mail}selected="selected"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="emailField" class="attval" title="{$mail|escape}">{$mail|escape|default:"<span style=\"color: red\">$l10n_value_undefined</span>"}</td>
      </tr>

      {*
       * entitlement
       *}
      <tr>
	<td align="right">{$l10n_label_entitlement}<br /></td>
	<td align="right">
	  <select name="entitlement" onchange="fetchAttributeValue(this, 'entitlementField', '{$l10n_err_attvalna}')">
	    <option value="&nbsp;">&nbsp; </option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.entitlement}selected="selected"{/if} value="{$element}">
	      {$element|escape}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="entitlementField" class="attval" title="{$entitlement|escape}">{$entitlement|escape|default:"<span style=\"color: red\">$l10n_value_undefined</span>"}</td>
      </tr>

      <tr>
	<td><br /></td><td></td><td></td>
      </tr>

      <tr>
	<td align="right"><input type="reset" value="reset"/></td>
	<td align="right">
	  <input type="submit"
		 value="update map"
		 {if $person->isNRENAdmin()}
		 onclick="return confirm('{$l10n_confirm_attreset} {$l10n_label_nren} {$nren->getName()|escape}')" />
		 {else}
		 onclick="return confirm('{$l10n_confirm_attreset} {$l10n_label_subscriber} {$subscriber->getOrgName()|escape}')" />
	  {/if}

	</td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
<br />
{/if}
