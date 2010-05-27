{if $person->isNRENAdmin() || $person->isSubscriberAdmin()}
{* avoid problems with hidden overflow and notification messages *}
<div>
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
    <div>{$panticsrf}</div>
    <table class="mapping">
      <tr>
	<th align="right">{$l10n_th_category}</th>
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
	  <select
	     {if ! $person->isNRENAdmin()}disabled="disabled"{/if}
	    name="epodn"
	    onchange="fetchAttributeValue(this, 'orgNameField', '{$l10n_err_attvalna}', '{$ganticsrf}');">
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
	  <select name="cn"
		  onchange="fetchAttributeValue(this, 'cnField', '{$l10n_err_attvalna}', '{$ganticsrf}');">
	    <option value="">&nbsp; </option>
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
	  <select name="mail"
		  onchange="fetchAttributeValue(this, 'emailField', '{$l10n_err_attvalna}', '{$ganticsrf}');">
	    <option value="">&nbsp; </option>
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
	  {* activate the entitlement field only for NREN-admins *}
	  <select
	     {if ! $person->isNRENAdmin()}
	    disabled="disabled"
	    {/if}
	    name="entitlement"
	    onchange="fetchAttributeValue(this, 'entitlementField', '{$l10n_err_attvalna}', '{$ganticsrf}')">
	    <option value="">&nbsp; </option>
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
</div>
{/if}
