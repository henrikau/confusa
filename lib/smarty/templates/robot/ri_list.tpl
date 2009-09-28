<fieldset>
<legend>Manage certificates</legend>
<br />
<p class="info">
This is the list of certificates. You may display the certificates in
greater detail.
</p>

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
    <td>
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
    <td width="20px"></td>
    <td>Serial number:</td>
    <td width="30px"></td>
    <td>{$element->serial()}</td>
  </tr>
  <tr>
    <td></td>
    <td>Uploaded by</td>
    <td></td>
    <td>{$element->getOwner()}</td>
  </tr>
  <tr>
    <td></td>
    <td>Uploaded:</td>
    <td width="30px"></td>
    <td>{$element->madeAvailable()}</td>
  </tr>
  <tr>
    <td></td>
    <td>Valid until:</td>
    <td></td>
    <td>{$element->validTo()}</td>
  </tr>
{/foreach}
</table>
{else}
No available certificates in Database.
<br />
{/if}
<br />
</fieldset>

