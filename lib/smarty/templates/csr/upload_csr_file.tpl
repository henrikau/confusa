<form action="process_csr.php?show=upload_csr" method="post" enctype="multipart/form-data">
  <div class="csr">
    <fieldset>
      <legend>Upload new CSR</legend>
      <div class="spacer"></div>
      <p class="info">
	Upload a local CSR for signing by the CA. If you created
	this with any globus-specific tools, you should look for
	the folder ".globus" in your home directory.
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
	      <input type="submit" value="Upload CSR" />
	    </div>
	  </td>
	</tr>
      </table>
      <br />
    </fieldset>
  </div>
</form>
