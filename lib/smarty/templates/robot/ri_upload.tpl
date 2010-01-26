<fieldset>
  <legend>Paste new certificate for <i>{$subscriber->getOrgName()|escape}</i></legend>
  <br />
  <form action="?robot_view=list" method="post">
    <p>
      <input type="hidden" name="robot_action" value="paste_new" />
    </p>
    <table>
      <tr>
	<td colspan="2">
	  Paste your certificate here:
	  <textarea name="cert" rows="20" cols="70"></textarea><br />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
      </tr>
      <tr>
	<td colspan="2">
	  Add an additional comment:
	  <textarea name="comment" rows="10" cols="70"></textarea><br />
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
  <legend>Upload new certificate for <i>{$subscriber->getOrgName()|escape}</i></legend>
  <br />
  <form enctype="multipart/form-data" action="" method="post">
    <p>
      <input type="hidden" name="robot_action" value="upload_new" />
      <input type="hidden"
	     name="MAX_FILE_SIZE"
	     value="2000000" />
    </p>
    <table>
      <tr>
	<td colspan="2">
	  <input name="cert" type="file" />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
      </tr>

      <tr>
	<td colspan="2">
	  Add an additional comment:
	  <textarea name="comment" rows="10" cols="70"></textarea>
	</td>
      </tr>
      <tr>
	<td><input type="reset" class="button" value="Reset form" /></td>
	<td><input type="submit" value="Upload Certificate" /></td>
      </tr>
  </table>
  </form>
  <br />
</fieldset>
