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
  <p>
    <input type="hidden" name="setting" value="nren_contact" />
  </p>
  <table>
    <tr>
      <td></td>
      <td style="width: 25px"><div class="spacer"></div></td>
      <td></td>
    </tr>

  <tr>
    <td align="right">Contact-email:</td>
    <td></td>
	{if isset($nrenInfo.contact_email)}
		<td><input type="text" name="contact_email" value="{$nrenInfo.contact_email}" /></td>
	{else}
		<td><input type="text" name="contact_email" value="" /></td>
	{/if}
  </tr>
    <tr>
      <td>NREN Phone</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.contact_phone)}
	<input type="text" name="contact_phone"
	value="{$nrenInfo.contact_phone}" />
	  {else}
		<input type="text" name="contact_phone" value="" />
	  {/if}
      </td>
    </tr>
    <tr>
      <td>CERT (email)</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.cert_email)}
	<input type="text" name="cert_email"
	value="{$nrenInfo.cert_email}" />
	  {else}
	<input type="text" name="cert_email"
	value="" />
	  {/if}
      </td>
    </tr>
    <tr>
      <td>CERT (phone)</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.cert_phone)}
	<input type="text" name="cert_phone"
	value="{$nrenInfo.cert_phone}" />
	  {else}
	<input type="text" name="cert_phone"
	value="" />
	  {/if}
      </td>
    </tr>
    <tr>
      <td>url</td>
      <td></td>
      <td>
	  {if isset($nrenInfo.url)}
	<input type="text" name="url" value="{$nrenInfo.url}"/>
	  {else}
	<input type="text" name="url" value="" />
	  {/if}
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
<br />
</fieldset>
