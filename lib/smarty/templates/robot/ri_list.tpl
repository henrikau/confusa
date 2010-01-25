
<fieldset>
<legend>{$l10n_legend_managecerts}</legend>
<br />
<p class="info">
{$l10n_infotext_managecerts1}
</p>
<p class="info">
{$l10n_infotext_managecerts2}
</p>
<hr width="90%"/>
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
	alt="Delete" title="{$l10n_title_deleterc}" class="url">
	{$l10n_label_deleterc}
    </a>
    </td>
    <td>
    </td>
    <td>
      <a href="?robot_action=info&serial={$element->serial()}">
	<img src="https://slcstest.uninett.no/silk_icons/information.png"
	alt="Info" title="{$l10n_title_inforc}" class="url"/>
	{$l10n_label_inforc}
    </a>
    </td>
  </tr>
  <tr>
    <td width="20px"></td>
    <td>{$l10n_label_serialnumber}</td>
    <td width="30px"></td>
    <td>{$element->serial()|escape}</td>
  </tr>
  <tr>
    <td></td>
    <td>{$l10n_label_uploadedby}</td>
    <td></td>
    <td>{$element->getOwner()|escape}</td>
  </tr>
  <tr>
    <td></td>
    <td>{$l10n_label_uploadeddate}</td>
    <td width="30px"></td>
    <td>{$element->madeAvailable()|escape}</td>
  </tr>
  <tr>
    <td></td>
    <td>{$l10n_label_validuntil}</td>
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
    <td><b>{$l10n_label_extendedinfo}</b><br /></td>
  </tr>
  <tr><td></td>
    <td>{$l10n_label_fingerprint}</td>
    <td></td>
    <td>{$element->fingerprint()|escape}</td>
  </tr>
  <tr><td></td>
    <td>{$l10n_label_comment}</td><td></td>
    <td>{$element->getComment()|escape}
    </td>
  </tr>
  {if $element->getLastWarningSent()}
  <tr><td></td>
    <td>{$l10n_label_warnsent}</td>
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
{$l10n_infotext_nocertavail}
<br />
{/if}
<br />
</fieldset>

