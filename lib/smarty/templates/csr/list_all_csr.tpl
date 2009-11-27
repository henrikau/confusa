<div class="csr">
  <fieldset>
    <legend>List of CSR</legend>

    <table>
      <tr>
	<td></td>
      </tr>
      <tr>
	<td><b>Upload date</b></td>
	<td></td>
	<td><b>Common name</b></td>
	<td></td>
	<td><b>Remote IP</b></td>
	<td></td>
	<td style="width: 20px">{*<b>Inspect</b>*}</td>
	<td></td>
	<td style="width: 20px">{*<b>Delete</b>*}</td>
	<td></td>
      </tr>
      {foreach from=$csrList item=csr}
      <tr><td></td></tr>
      <tr>
	<td>{$csr.uploaded_date|escape}</td>
	<td> </td>
	<td>{$csr.common_name|escape}</td>
	<td> </td>
	<td>{$csr.from_ip}</td>
	<td> </td>
	<td>
	  <a href="process_csr.php?inspect_csr={$csr.auth_key}"><img src="graphics/information.png"
	  alt="Inspect" title="Inspect CSR" class="url" /></a>
	</td>
	<td></td>
	<td>
	  <a href="?delete_csr={$csr.auth_key}"><img src="graphics/delete.png"
	  alt="Delete" title="Delete CSR from database" class="url"/></a>
	</td>
	<td></td>

      </tr>

      {if isset($csrInspect) && $csrInspect.auth_token eq $csr.auth_key}
    </table>
    <br />
    {$inspect_csr}
    <table>
      {else}

      {/if}
      {/foreach}
      <tr>
	<td></td>
      </tr>
    </table>
  </fieldset>
</div>
