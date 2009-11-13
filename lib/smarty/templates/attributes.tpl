{if $person->isNRENAdmin() || $person->isSubscriberAdmin()}
{literal}
<script type="text/javascript">

	if (typeof XMLHttpRequest == "undefined") {
		XMLHttpRequest = function() {
			/* define XMLHttpRequest for IE versions < 7 */
			try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
			catch(e) {}
			try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
			catch(e) {}
			try { return new ActiveXObject("Msxml2.XMLHTTP"); }
			catch(e) {}
			try { return new ActiveXObject("Microsoft.XMLHTTP"); }
			catch(e) {}
		};
	}

	/**
	 * Ask the stylist for the value of the attribute key currently selected
	 * in 'selectElement' with a polite asynchronous GET message
	 *
     * @param selectElement DOM-Node The <select> containing the selected
     *                      attribute key
     * @param targetElementID string The ID of the element where the value
     *                        should be written to
     * @return void
     */
	function fetchAttributeValue(selectElement, targetElementID)
	{
		var req = new XMLHttpRequest();
		var field = document.getElementById(targetElementID);

		req.open("GET", "?attr_value=" + selectElement.value, true);
		req.send(null);
		req.onreadystatechange = function() {
			if (req.readyState == 4 /*complete*/) {
				if (req.status == 200) {
					field.innerHTML = req.responseText;
					field.title = req.responseText;
				} else {
					field.innerHTML = "<i>Attribute value could not be retrieved</i>";
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
  <legend>Create or modify NREN attribute-map</legend>
  <p class="info">
    <br />
    Add or update the attribute map for your NREN
    ({$person->getNREN()|escape}). This part is crucial for the operation of
    Confusa, and if the mapping is not working properly, the users may
    not be able to use Confusa the way they are supposed to.
  </p>
  <p class="info">
    <br />
    It is also possible to add subscriber-specific maps. If a subscriber
    has this set, this map will be used before any NREN-specific
    map. Note that the subscriber-map is added as a way of helping
    subscribers with severe limitations to what they can (or will)
    export to Confusa and should only be used as an absolute last
    resort.
  </p>
{elseif $person->isSubscriberAdmin()}
	<legend>Create or modify subscriber attribute-map</legend>
	<p class="info">
		<br />
		Add or update the attribute map for your subscriber
		'{$person->getSubscriberIdPName()|escape}'.
		Usually this map should have already been defined by a NREN admin and
		the NREN-wide settings for your NREN '{$person->getNREN()|escape}'
		should also apply for your subscriber.
	</p>
	<p class="info">
		<br />
		So it is advised to change this map only if your IdP really requires
		to send different attributes than the NREN-wide setting defines.
	</p>
{/if}

  <br />
  <br />

  <form action="" method="post">
    <table class="mapping" width="95%" border="1" rules="none" cellpadding="5" cellspacing="5">
      <tr>
	<th align="left">Category</th>
	<th align="center">Current Key</th>
	<th align="left">Value</th>
      </tr>
      <tr>
	<td align="right">Country</td>
	<td align="center"><b><span style="color: darkgray">-</span></b></td>
	<td>{$person->getCountry()|escape}
	<input type="hidden" name="attributes_operation" value="update_map" />
	</td>
      </tr>


      {*
      * Global, unique and traceable id 'eppn' aka
      * eduPersonPrincipalName
      *}
      <tr>
	<td align="right">Unique identifier</td>
	<td align="center"><span style="color: darkgray">{$person->getEPPNKey()|escape}</span></td>
	<td>{$person->getEPPN()|escape|default:"<span style=\"color: red\">undefined</span>"}</td>
      </tr>

      {*
       * Organization 'epodn'
       *}
      <tr>
	<td align="right">Organization<br /></td>
	<td align="right">
	  <select {if ! $person->isNRENAdmin()} disabled="disabled"{/if} name="epodn" onchange="fetchAttributeValue(this, 'orgNameField');">
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.epodn}selected="selected"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="orgNameField" class="attval" title="{$epodn|escape}">{$epodn|escape|default:"<span style=\"color: red\">undefined</span>"}</td>
      </tr>


      {*
       * Full Name 'cn'
       *}
      <tr>
	<td align="right">Full Name<br /></td>
	<td align="right">
	  <select name="cn" onchange="fetchAttributeValue(this, 'cnField');">
	    <option value=""></option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.cn}selected="selected"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="cnField" class="attval" title="{$cn|escape}">{$cn|escape|default:"<span style=\"color: red\">undefined</span>"}</td>
      </tr>

      {*
       * mail
       *}
      <tr>
	<td align="right">E-Mail<br /></td>
	<td align="right">
	  <select name="mail" onchange="fetchAttributeValue(this, 'emailField');">
	    <option value=""></option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.mail}selected="selected"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="emailField" class="attval" title="{$mail|escape}">{$mail|escape|default:"<span style=\"color: red\">undefined</span>"}</td>
      </tr>

      {*
       * entitlement
       *}
      <tr>
	<td align="right">entitlement<br /></td>
	<td align="right">
	  <select name="entitlement" onchange="fetchAttributeValue(this, 'entitlementField')">
	    <option value=""></option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $map.entitlement}selected="selected"{/if} value="{$element}">
	      {$element|escape}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td id="entitlementField" class="attval" title="{$entitlement|escape}">{$entitlement|escape|default:"<span style=\"color: red\">undefined</span>"}</td>
      </tr>

      <tr>
	<td><br /></td><td></td><td></td>
      </tr>

      <tr>
	<td align="right"><input type="reset" value="reset"/></td>
	<td align="right">
	  <input type="submit" value="update map" onclick="return confirm('\tAre you sure?\n\nThis will potentially affect all users affiliated with NREN {$person->getNREN()|escape}!')" />
	</td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
<br />
{/if}