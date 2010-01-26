
<fieldset>
<legend>Manage certificates</legend>
<br />
<p class="info">
  In this section you will find information about already uploaded
  certificates for your organization. You can modify ownership if the
  certificate is listed as an orphan, or you can remove it all
  together.
</p>
<p class="info">
  If you so choose, you may inspect the certificate in greater detail
  here.
</p>
<hr style="width: 90%;"/>
{if $robotCerts}
<table>
{foreach from=$robotCerts item=element}
  <tr>
    <td><div class="spacer"></div></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td></td>
    <td valign="top">
      <a href="?robot_action=delete&serial={$element->serial()}">
	<img src="https://slcstest.uninett.no/silk_icons/delete.png"/
	alt="Delete" title="Delete Robot Certificate" class="url">
	Delete
    </a>
    </td>
    <td>
    </td>
    <td>
      <a href="?robot_action=info&serial={$element->serial()}">
	<img src="https://slcstest.uninett.no/silk_icons/information.png"
	alt="Info" title="Get more information about certificate" class="url"/>
	Information
    </a>
    </td>
  </tr>
  <tr>
    <td style="width: 20px;"></td>
    <td>Serial number:</td>
    <td style="width: 30px;"></td>
    <td>{$element->serial()|escape}</td>
  </tr>
  <tr>
    <td></td>
    <td>Uploaded by</td>
    <td></td>
    <td>{$element->getOwner()|escape}</td>
  </tr>
  <tr>
    <td></td>
    <td>Uploaded:</td>
    <td width="30px"></td>
    <td>{$element->madeAvailable()|escape}</td>
  </tr>
  <tr>
    <td></td>
    <td>Valid until:</td>
    <td></td>
    <td>{$element->validTo()|escape}</td>
  </tr>

  {if $cert_info}
  <tr>
    <td>
      <div class="spacer"></div>
    </td>
  </tr>

  { if $element->serial() eq $cert_info_serial}
  <tr><td></td>
    <td></td>
    <td></td>
    <td><b>Extended Info</b><br /></td>
  </tr>
  <tr><td></td>
    <td>Fingerprint:</td>
    <td></td>
    <td>{$element->fingerprint()|escape}</td>
  </tr>
  <tr><td></td>
    <td>Comment:</td><td></td>
    <td>{$element->getComment()|escape}
    </td>
  </tr>
  {if $element->getLastWarningSent()}
  <tr><td></td>
    <td>Warning sent:</td>
    <td></td>
    <td>{$element->getLastWarningSent()|escape}
    </td>
  </tr>
  {/if}
  <tr><td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  {/if}
  {/if}
  {/foreach}
</table>
{else}
No available certificates in Database.
<br />
{/if}
<br />
</fieldset>

