<fieldset>
  <legend>Subscriber contact information</legend>
  <br />
  <p class="info">
    Here you can define contact information for your subscriber as well as
    language settings. The e-mail address should point to more than one
    person so that the chance of an immediate response is high in case of
    emergency. For instance, Confusa might contact this address in case of
    critical errors or to notify you of mass-revocation of certificates.
  </p>

  <form method="post" action="">
    <input type="hidden" name="setting" value="subscriber_contact" />
    <table>
      <tr>
	<td></td>
	<td width="25px"><div class="spacer"></div></td>
	<td></td>
      </tr>
      <tr>
	<td align="right">Contact-email:</td>
	<td></td>
	<td><input type="text" name="contact_email" value="{$subscriberInfo.subscr_email}" /></td>
      </tr>
      <tr>
	<td align="right">Contact-phone:</td>
	<td></td>
	<td><input type="text" name="contact_phone" value="{$subscriberInfo.subscr_phone}" /></td>
      </tr>
      <tr>
	<td align="right">Responsible Name:</td>
	<td></td>
	<td><input type="text" name="resp_name" value="{$subscriberInfo.subscr_resp_name}" /></td>
      </tr>

      <tr>
	<td align="right">Responsible's email:</td>
	<td></td>
	<td><input type="text" name="resp_email" value="{$subscriberInfo.subscr_resp_email}" /></td>
      </tr>

      <tr>
	<td align="right">
	  <input type="reset" value="reset" />
	</td>
	<td></td>
	<td>
	  <input type="submit" value="Update" />
	</td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
