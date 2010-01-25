<fieldset>
  <legend>{$l10n_legend_pastenew} <i>{$subscriber->getOrgName()|escape}</i></legend>
  <br />
  <form action="?robot_view=list" method="post">
    <input type="hidden" name="robot_action" value="paste_new" />
    <table>
      <tr>
	<td colspan="2">
	  {$l10n_label_pastehere}
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
	  {$l10n_label_addcomment}
	  <textarea name="comment" value="" rows="10" cols="70"
	  wrap="off"></textarea><br />
	</td>
      </tr>
      <tr>
	<td><input type="reset" class="button" value="{$l10n_button_reset}" /></td>
	<td><input type="submit" class="button" value="{$l10n_button_upload}"/><br /></td>
      </tr>
    </table>
  </form>
</fieldset>

<br />
<fieldset>
  <legend>{$l10n_legend_uploadnew} <i>{$subscriber->getOrgName()|escape}</i></legend>
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
	  {$l10n_label_addcomment}
	  <textarea name="comment" value="" rows="10" cols="70"
	  wrap="off"></textarea>
	</td>
      </tr>
      <tr>
	<td><input type="reset" class="button" value="{$l10n_button_reset}" /></td>
	<td><input type="submit" value="{$l10n_button_upload}" /></td>
      </tr>
  </form>
  </table>
  <br />
</fieldset>
