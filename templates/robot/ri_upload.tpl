<fieldset>
  <legend>{$l10n_legend_pastenew} <i>{$subscriber->getOrgName()|escape}</i></legend>
  <br />
  <form action="?robot_view=list" method="post">
    <p>
      <input type="hidden" name="robot_action" value="paste_new" />
    </p>
    <table>
      <tr>
	<td colspan="2">
	  {$l10n_label_pastehere}
	  <textarea name="cert" value="" rows="20" cols="70"></textarea><br />
	</td>
      </tr>
      <tr>
	<td><div class="spacer"></div></td>
	<td></td>
      </tr>
      <tr>
	<td colspan="2">
	  {$l10n_label_addcomment}
	  <textarea name="comment" value="" rows="10" cols="70"></textarea><br />
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
	  {$l10n_label_addcomment}
	  <textarea name="comment" value="" rows="10" cols="70"></textarea>
	</td>
      </tr>
      <tr>
	<td><input type="reset" class="button" value="{$l10n_button_reset}" /></td>
	<td><input type="submit" value="{$l10n_button_upload}" /></td>
      </tr>
  </table>
  </form>
  <br />
</fieldset>
