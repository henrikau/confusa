{* The display part *}
<p class="info">
  You can now revoke all your certificates. Do to this, you must first
  specify a reason, and then press 'Revoke all'.
</p>
<p class="info">
  If you want to revoke a specific certificate, you must go
  to <a href="download_certificate.php">My Certificates</a> and choose
  the particular certificate to revoke.
</p>

{if isset($owners)}
    {if $revoke_cert}

        {foreach from=$owners item=owner}
	<hr style="width:90%" />
	<br />
	<i><b>{$owner|escape|replace:',':', '}</b></i>
	<br />
	<br />
        <form action="" method="post">
	    <table>
	      <tr>
		<td>
	    <input type="hidden" name="revoke_operation" value="revoke_by_cn" />
	    <input type="hidden" name="common_name" value="{$owner}" />
              {html_radios	name="reason"
				values="$nren_reasons"
				output="$nren_reasons"
				selected="$selected"
				separator="<br />"}

		</td>
		<td style="width: 50px"></td>
		<td>
		  <input type="submit"
			 name="submit"
			 value="Revoke all"
			 onclick="return confirm('Revoking {$stats[$owner]} certificates! Are you sure?')" />
		</td>
	      </tr>
	    </table>
        </form>
	<br />
	<br />
        {/foreach}
    {/if}
{/if}

