<h3>Robot Interface</h3>
<br />
<p class="info">
This is where you administer the robotic interface for your
institution. You can upload new certificates that will be accepted by
Confusa, or you can delete certificates from the store. When you delete
a certificate, that certificate will no longer be accepted for robotic
actions.
</p>
<br />
<fieldset>
<legend>Manage certificates</legend>
<br />
<p class="info">List of uploaded certificates with details.</p>
{if $robotCerts}
<table>
{foreach from=$robotCerts item=element}
  <tr></tr>
  <tr>
    <td>Uploaded:</td>
    <td width="30px"></td>
    <td>{$element.uploaded_date}</td>
  </tr>
  <tr>
    <td>Uploaded by</td>
    <td></td>
    <td>{$element.admin}</td>
  </tr>
  <tr>
    <td>Valid until:</td>
    <td></td>
    <td>{$element.valid_until}</td>
  </tr>
  <tr>
    <td>comment</td>
    <td></td>
    <td>{$element.comment}</td>
  </tr>
{/foreach}
</table>
{/if}
<br />
</fieldset>

<br />
<fieldset>
  <legend>Add new certificate for <i>{$person->getSubscriberOrgName()}</i></legend>
  <br />
  <form action="" method="post">
    <input type="hidden" name="robot_action" value="add_new" />
    <table>
      <tr>
	<td colspan="2">
	  <textarea name="cert" value="" rows="20" cols="70"
	  wrap="off">Paste your certificate here...</textarea><br />
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

