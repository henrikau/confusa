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
  <tr></tr>
  <tr>
    <td width="20px"></td>
    <td>Fingerprint</td>
    <td width="30px"></td>
    <td>{$element.fingerprint}</td>
  </tr>
  <tr>
    <td><img src="https://slcstest.uninett.no/silk_icons/date.png" /></td>
    <td>Uploaded:</td>
    <td width="30px"></td>
    <td>{$element.uploaded_date}</td>
  </tr>
  <tr>
    <td><img src="https://slcstest.uninett.no/silk_icons/user_suit.png"/></td>
    <td>Uploaded by</td>
    <td></td>
    <td>{$element.admin}</td>
  </tr>
  <tr>
    <td><img src="https://slcstest.uninett.no/silk_icons/date_delete.png" /></td>
    <td>Valid until:</td>
    <td></td>
    <td>{$element.valid_until}</td>
  </tr>
  <tr>
    <td><img src="https://slcstest.uninett.no/silk_icons/comment.png" /></td>
    <td>comment:</td>
    <td></td>
    <td>{$element.comment}</td>
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
	  <textarea name="cert" value="" rows="20" cols="70"
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
    <input name="cert" TYPE="file" />
    <input type="submit" value="Upload Certificate" />
  </form>
  <br />
</fieldset>

<br />

