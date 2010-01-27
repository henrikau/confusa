<div class="csr">
  <fieldset class="inpsect_csr">
    <legend>{$legendTitle}</legend>
    <p class="info">
       {$l10n_infotext_signcert1}
    </p>

    <table>
      <tr><td></td></tr>
      {* Auth Token *}
      <tr>
	<td>{$l10n_label_authtoken}</td>
	<td></td>
	<td>{$csrInspect.auth_token|escape}</td>
      </tr>
      <tr><td></td></tr>

      {* Country *}
      {if !empty($csrInspect.countryName)}
      <tr>
	<td>{$l10n_label_country}</td>
	<td></td>
	<td>{$csrInspect.countryName|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Organization name *}
      {if !empty($csrInspect.organizationName)}
      <tr>
	<td>{$l10n_label_orgname}</td>
	<td></td>
	<td>{$csrInspect.organizationName|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Common-Name *}
      {if !empty($csrInspect.commonName)}
      <tr>
	<td>{$l10n_label_cn}</td>
	<td></td>
	<td>{$csrInspect.commonName|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Length of key *}
      {if !empty($csrInspect.length)}
      <tr>
	<td>{$l10n_label_keyl}</td>
	<td></td>
	<td>{$csrInspect.length|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Uploaded *}
      {if !empty($csrInspect.length)}
      <tr>
	<td>{$l10n_label_uploadedwhen}</td>
	<td></td>
	<td>{$csrInspect.uploaded|escape}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Remote IP *}
      {if !empty($csrInspect.length)}
      <tr>
	<td>{$l10n_label_ip}</td>
	<td></td>
	<td>{$csrInspect.from_ip}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      <tr>
	<td>
	  <a href="?delete_csr={$csrInspect.auth_token}"><img src="graphics/delete.png"
	  alt="{$l10n_title_deletecsr}" title="{$l10n_title_deletecsr}" class="url"/> {$l10n_link_delete}</a>
	</td>
	<td></td>
	<td>
	  <a href="?sign_csr={$csrInspect.auth_token}"><img src="graphics/accept.png"
	  alt="{$l10n_title_approvecsr}" title="{$l10n_title_approvecsr}" class="url"/> {$l10n_link_approvecsr}</a>
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
