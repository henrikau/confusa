<fieldset>
  <legend>Create attribute-map</legend>
  <p class="info">
    Add or update the attribute map for your NREN ({$nren_name}). This
    part is crucial for the operation of Confusa, and if the mapping is
    not working properly, the users may not be able to use Confusa the
    way they are supposed to.
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

  <form input="post" action="">
    <table width="75%">
      <tr>
	<td>eduPersonPrincipalName</td><td>meh</td>
      </tr>

      <tr>
	<td><br /></td><td></td>
      </tr>
      <tr>
	<td><input type="reset" name="clear"/></td>
	<td><input type="submit" name="create map" /></td>
      </tr>
    </table>
  </form>
</fieldset>
