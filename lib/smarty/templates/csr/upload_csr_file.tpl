<div class="csr">
  <fieldset>
    <legend>Upload new CSR</legend>
    <br />
    <p class="info">
      Upload a local CSR for signing by the CA. If you created
      this with any globus-specific tools, you should look for
      the folder ".globus" in your home directory.
    </p>
    <table>
      <tr>
	<td>
	  <form action="process_csr.php" method="post" enctype="multipart/form-data">
	    <div><!-- XHTML strict won't allow inputs just within forms -->
	      <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	      <input type="file" name="user_csr" />
	      <input type="submit" value="Upload CSR" />
	    </div>
	  </form>
	</td>
      </tr>
    </table>
  </fieldset>
