<form action="process_csr.php?show=upload_csr" method="post" enctype="multipart/form-data">
  <div class="csr">
    <fieldset>
      <legend>{$l10n_legend_uploadnewcsr}</legend>
      <div class="spacer"></div>
      <p class="info">
		{$l10n_infotext_uploadnewcsr1}
      </p>
      <div class="spacer"></div>
      {include file="csr/email.tpl"}
      <div class="spacer"></div>
      <table>
	<tr>
	  <td>
	    <div><!-- XHTML strict won't allow inputs just within forms -->
	      <input type="hidden" name="uploadedCSR" value="uploadedCSR" />
	      <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	      <input type="file" name="user_csr" />
	      <input type="submit" value="{$l10n_button_uploadcsr}" />
	    </div>
	  </td>
	</tr>
      </table>
      <br />
    </fieldset>
  </div>
</form>
