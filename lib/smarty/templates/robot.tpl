<h3>Robot Interface</h3>
<br />
<p class="info">
In this section you will find information about already uploaded
certificates for your organization. You can modify ownership if the
certificate is listed as an orphan, or you can remove it all
together. You may also upload a new certificate, either by pasting into
the window below, or by uploading a local file.
</p>

<br />
<fieldset>
<legend>Manage certificates</legend>
<br />
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

<br />
<fieldset>
  <legend>Paste new certificate for <i>{$person->getSubscriberOrgName()}</i></legend>
  <br />
  <form action="" method="post">
    <input type="hidden" name="robot_action" value="paste_new" />
    <table>
      <tr>
	<td colspan="2">
	  Paste your certificate here:
	  <textarea name="cert" value="" rows="20" cols="70"
		    wrap="off"></textarea><br />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
      </tr>
      <tr>
	<td colspan="2">
	  Add an additional comment:
	  <textarea name="comment" value="" rows="10" cols="70"
	  wrap="off"></textarea><br />
	</td>
      </tr>
      <tr>
	<td><input type="reset" class="button" value="Reset form" /></td>
	<td><input type="submit" class="button" value="Upload Certificate"/><br /></td>
      </tr>
    </table>
  </form>
</fieldset>

<br />
<fieldset>
  <legend>Upload new certificate for <i>{$person->getSubscriberOrgName()}</i></legend>
  <br />
  <form enctype="multipart/form-data" action="" METHOD="POST">
    <input type="hidden" name="robot_action" value="upload_new" />
    <input type="hidden"
	   name="MAX_FILE_SIZE"
	   value="2000000" />
    <table>
      <tr>
	<td colspan="2">
	  <input name="cert" TYPE="file" />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
      </tr>

      <tr>
	<td colspan="2">
	  Add an additional comment:
	  <textarea name="comment" value="" rows="10" cols="70"
	  wrap="off"></textarea>
	</td>
      </tr>
      <tr>
	<td><input type="reset" class="button" value="Reset form" /></td>
	<td><input type="submit" value="Upload Certificate" /></td>
      </tr>
  </form>
  </table>
  <br />
</fieldset>

<br />

