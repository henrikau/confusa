<form action="upload_csr.php" method="post">
<div class="csr">
  <fieldset class="inpsect_csr">
    <legend>{$legendTitle}</legend>
    <p class="info">
       {$l10n_infotext_signcert1}
    </p>
	<div style="border-style: inset; border-width: 1px; padding: 0.5em">
	<p class="info" >
		<strong>{$l10n_label_finalCertDN}</strong>
	</p>
	<p style="font-size: 1em; font-family: monospace; margin-bottom: 1em">
		{$finalDN}
	</p>
	</div>

    <table style="margin-top: 2em">
      <tr><td></td></tr>
      {* Auth Token *}
      <tr>
	<td>{$l10n_label_authtoken}</td>
	<td></td>
	<td>{$authToken|escape}</td>
      </tr>
      <tr><td></td></tr>

      {* Subject *}
      {if !empty($subject)}
      <tr>
	<td>{$l10n_label_subject}:</td>
	<td></td>
	<td>{$subject|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Length of key *}
      {if !empty($length)}
      <tr>
	<td>{$l10n_label_keyl}</td>
	<td></td>
	<td>{$length|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Uploaded *}
      {if !empty($uploadedDate)}
      <tr>
	<td>{$l10n_label_uploadedwhen}</td>
	<td></td>
	<td>{$uploadedDate|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Remote IP *}
      {if !empty($uploadedFromIP)}
      <tr>
	<td>{$l10n_label_ip}</td>
	<td></td>
	<td>{$uploadedFromIP}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      <tr><td></td></tr>

      <tr>
	<td></td>
	<td></td>
	<td></td>
      </tr>
    </table>
  </fieldset>

  <div style="float: right;" class="nav">
		{$panticsrf}
		<input type="hidden" name="signCSR" value="{$authToken}" />
		<input id="nextButton" type="submit" class="nav" title="{$l10n_button_next}" value="{$l10n_button_next} &gt;" />
  </div>
</form>

<form action="receive_csr.php" method="post">
<div style="float: right;" class="nav">
	{$panticsrf}
	<input type="hidden" name="deleteCSR" value="{$authToken}" />
	<input id="backButton" class="nav" type="submit" title="{$l10n_button_back}" value="&lt; {$l10n_button_back}" />
</div>
</form>
</div> <!-- inspect_csr -->
