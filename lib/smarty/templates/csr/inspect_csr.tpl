<div class="csr">
  <fieldset class="inpsect_csr">
    {*<legend>Inspect CSR</legend>*}
    <table>
      <tr><td></td></tr>
      {* Auth Token *}
      <tr>
	<td>Auth token</td>
	<td></td>
	<td>{$csrInspect.auth_token}</td>
      </tr>
      <tr><td></td></tr>

      {* Country *}
      {if !empty($csrInspect.countryName)}
      <tr>
	<td>Country:</td>
	<td></td>
	<td>{$csrInspect.countryName}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Organization name *}
      {if !empty($csrInspect.organizationName)}
      <tr>
	<td>Organization Name:</td>
	<td></td>
	<td>{$csrInspect.organizationName}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Common-Name *}
      {if !empty($csrInspect.commonName)}
      <tr>
	<td>Common-Name:</td>
	<td></td>
	<td>{$csrInspect.commonName}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Length of key *}
      {if !empty($csrInspect.length)}
      <tr>
	<td>Key length:</td>
	<td></td>
	<td>{$csrInspect.length}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Uploaded *}
      {if !empty($csrInspect.length)}
      <tr>
	<td>Was uploaded:</td>
	<td></td>
	<td>{$csrInspect.uploaded}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      {* Remote IP *}
      {if !empty($csrInspect.length)}
      <tr>
	<td>IP:</td>
	<td></td>
	<td>{$csrInspect.from_ip}</td>
      </tr>
      <tr><td></td></tr>
      {/if}

      <tr>
	<td>
	  [<a href="?delete_csr={$csrInspect.auth_token}">Delete</a>]
	</td>
	<td></td>
	<td>
	  [<a href="?sign_csr={$csrInspect.auth_token}">Approve</a>]
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
