<fieldset>
  <legend>NREN contact information</legend>
  <br />
  <p class="info">
    Here you can define contact information and language settings for your
    NREN. Define an e-mail address that is tied to one or more persons that
    will react if they receive notifications from Confusa. For instance,
    Confusa might contact this address in case of critical errors or to
    notify you of mass-revocation of certificates.
  </p>
  <form method="post" action="">
    <input type="hidden" name="setting" value="nren_contact" />
    <table>
      <tr>
	<td></td>
	<td width="25px"><div class="spacer"></div></td>
	<td></td>
      </tr>

      <tr>
	<td align="right">Contact-email:</td>
	<td></td>
	<td><input type="text" name="contact_email" value="{$nrenInfo.contact_email}" /></td>
      </tr>

      <tr>
	<td>NREN Phone</td>
	<td></td>
	<td>
	  <input type="text" name="contact_phone"
		 value="{$nrenInfo.contact_phone}" />
	</td>
      </tr>

      <tr>
	<td>CERT (email)</td>
	<td></td>
	<td>
	  <input type="text" name="cert_email"
		 value="{$nrenInfo.cert_email}" />
	</td>
      </tr>

      <tr>
	<td>CERT (phone)</td>
	<td></td>
	<td>
	  <input type="text" name="cert_phone"
		 value="{$nrenInfo.cert_phone}" />
	</td>
      </tr>

      <tr>
	<td>url</td>
	<td></td>
	<td>
	  <input type="text" name="url" value="{$nrenInfo.url}"/>
	</td>
      </tr>

      <tr>
	<td>Language</td>
	<td></td>
	<td>{html_options name="language" selected=$current_language
	  output=$languages values=$language_codes}</td>
      </tr>

      <tr>
	<td></td>
	<td></td>
	<td>
	  <div class="spacer"></div>
	</td>
      </tr>
      <tr>
	<td align="right"><input type="reset" value="reset" /></td>
	<td></td>
	<td><input type="submit" value="Update" /></td>
      </tr>
    </table>
  </form>
</fieldset>
