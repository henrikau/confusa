<?php
function show_upload_form($target)
{
	if (!isset($target))
		return;
?>
<FORM ENCTYPE="multipart/form-data" ACTION="<?php echo $target ?>" METHOD="POST">
  <INPUT TYPE="hidden" 
	 NAME="MAX_FILE_SIZE" 
	 VALUE="2000000" />
Please upload your CSR for signing. Signed certificates will be stored in the database.
    <BR />
    <INPUT NAME="user_csr" TYPE="file" />
  <INPUT TYPE="submit" VALUE="Upload CSR" />
</FORM>

<?php
} /* end show_upload_form */
?>