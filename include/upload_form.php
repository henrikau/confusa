<?php
  /**
   * show_upload_form - create an upload form for arbitrary data.
   *
   * @target_url  : the page that will handle the upload
   * @input_name  : the name of the field where the data is stored
   * @submit_name : If the name of the submit-button (upload) should be
   *		    changed.
   */
function show_upload_form($target_url, $input_name, $submit_name = NULL)
{
	if (!isset($target_url))
		return;
	if (!isset($input_name))
		return;
	$sname = "Upload";
	if (isset($submit_name))
		$sname = $submit_name;
?>
<FORM ENCTYPE="multipart/form-data" ACTION="<?php echo "$target_url"; ?>" METHOD="POST">
  <INPUT TYPE="hidden" 
	 NAME="MAX_FILE_SIZE" 
	 VALUE="2000000" />
Please upload your CSR for signing. Signed certificates will be stored in the database.
    <BR />
    <INPUT NAME="<?php echo $input_name ?>" TYPE="file" />
  <INPUT TYPE="submit" VALUE="<?php echo $sname ?>" />
</FORM>

<?php
} /* end show_upload_form */
?>