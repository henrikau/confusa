<fieldset>
  <legend>Paste new certificate for <i>{$person->getSubscriber()->getOrgName()|escape}</i></legend>
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
  <legend>Upload new certificate for <i>{$person->getSubscriber()->getOrgName()|escape}</i></legend>
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
