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

      <tr>
	<td>
	  <a href="?delete_csr={$authToken}&amp;{$ganticsrf}">
	    <img src="graphics/delete.png"
		 alt="{$l10n_title_deletecsr}"
		 title="{$l10n_title_deletecsr}"
		 class="url"/> {$l10n_link_delete}
	  </a>
	</td>
	<td></td>
	<td>
	  <a href="?sign_csr={$authToken}&amp;{$ganticsrf}">
	    <img src="graphics/accept.png"
		 alt="{$l10n_title_approvecsr}"
		 title="{$l10n_title_approvecsr}"
		 class="url"/> {$l10n_link_approvecsr}
	  </a>
	</td>
      </tr>
      <tr><td></td></tr>

      <tr>
	<td></td>
	<td></td>
	<td></td>
      </tr>
    </table>
  </fieldset>
</div> <!-- inspect_csr -->
