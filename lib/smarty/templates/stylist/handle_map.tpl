<fieldset>
  <legend>Create or modify NREN attribute-map</legend>
  <p class="info">
    <br />
    Add or update the attribute map for your NREN
    ({$person->getNREN()}). This part is crucial for the operation of
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

  <br />
  <br />

  <form action="" method="post">
    <table width="95%" border="1" rules="none" cellpadding="5" cellspacing="5">
      <input type="hidden" name="stylist_operation" value="update_map_nren" />
      <tr>
	<th align="left">Category</th>
	<th align="center">Current Key</th>
	<th align="left">Result</th>
      </tr>
      <tr>
	<td align="right">Country</td>
	<td align="center"><b><font color="darkgray">N/A</font></b></td>
	<td>{$person->getCountry()}</td>
      </tr>


      {*
      * Global, unique and traceable id 'eppn' aka
      * eduPersonPrincipalName
      *}
      <tr>
	<td align="right">Unique identifier</td>
	<td align="center"><b><font color="darkgray">{$person->getEPPNKey()}</font></b></td>
	<td>{$person->getEPPN()}</td>
      </tr>

      {*
       * Organization 'epodn'
       *}
      <tr>
	<td align="right">Organization<br /></td>
	<td align="right">
	  <select {if ! $person->isNRENAdmin()} DISABLED{/if} name="epodn">
	    {foreach from=$keys item=element}
	    <option {if $element eq $NRENMap.epodn}selected="yes"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td>{$person->getSubscriberOrgName()}</td>
      </tr>


      {*
       * Full Name 'cn'
       *}
      <tr>
	<td align="right">Full Name<br /></td>
	<td align="right">
	  <select name="cn">
	    <option value=""></option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $NRENMap.cn}selected="yes"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td>{$person->getName()}</td>
      </tr>

      {*
       * mail
       *}
      <tr>
	<td align="right">E-Mail<br /></td>
	<td align="right">
	  <select name="mail">
	    <option value=""></option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $NRENMap.mail}selected="yes"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td>{$person->getEmail()}</td>
      </tr>

      {*
       * entitlement
       *}
      <tr>
	<td align="right">entitlement<br /></td>
	<td align="right">
	  <select name="entitlement">
	    <option value=""></option>
	    {foreach from=$keys item=element}
	    <option {if $element eq $NRENMap.entitlement}selected="yes"{/if} value="{$element}">
	      {$element}
	    </option>
	    {/foreach}
	  </select>
	</td>
	<td>{$person->getEduPersonEntitlement()}</td>
      </tr>

      <tr>
	<td><br /></td><td></td><td></td>
      </tr>

      <tr>
	<td align="right"><input type="reset" value="reset"/></td>
	<td align="right">
	  <input type="submit" value="update map" onclick="return confirm('\tAre you sure?\n\nThis will potentianally affect all users affiliated with {$person->getSubscriberOrgName()}')" />
	</td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
<br />
