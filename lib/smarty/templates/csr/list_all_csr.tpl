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
	<td>{*<b>Inspect</b>*}</td>
	<td></td>
	<td>{*<b>Delete</b>*}</td>
	<td></td>
      </tr>
      {foreach from=$csrList item=csr}
      <tr><td></td></tr>
      <tr>
	<td>{$csr.uploaded_date}</td>
	<td> </td>
	<td>{$csr.common_name}</td>
	<td> </td>
	<td>{$csr.from_ip}</td>
	<td> </td>
	<td>
	  {if ! ($csrInspect.auth_token eq $csr.auth_key)}
	  [<a href="process_csr.php?inspect_csr={$csr.auth_key}">Inspect</a>]
	</td>
	<td></td>
	<td>[<a href="process_csr.php?delete_csr={$csr.auth_key}" alt="delete">Delete</a>]</td>
	<td></td>
	{else}
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	{/if}

      </tr>

      {if $csrInspect.auth_token eq $csr.auth_key}
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
