<?php 
require_once('confusa_include.php');
include_once('framework.php');
include_once('cert_manager.php'); /* for handling of the key */
include_once('file_upload.php'); /* FileUpload */
include_once('csr_lib.php');
include_once('mdb2_wrapper.php');
include_once('logger.php');
include_once('confusa_gen.php');
require_once("output.php");
require_once("pw.php");

$person = null;
$fw = new Framework('keyhandle');

/* test for downloading of certificates */
if (send_cert()) {
     exit(0);
}

/* Test to see if any of the flags that require AuthN are set */
if (process_csr_flags_set() || process_cert_flags_set()){
	$fw->force_login();
}

$fw->render_page();
/* The rest of this file si functions used in the preceding section. */



/**
 * keyhandle - main control function for handling CSRs and certificates
 *
 * It will make sure all CSRs and Certificates stored in the database will be
 * processed and displayed to the user properly.
 *
 * @pers : the person-object associated with this instance. If the person is
 *	   non-AuthN, a unclassified version will be displayed.
 */
function keyhandle($pers) 
{
  global $person;
  $person = $pers;
  if ($person->is_auth()) {
	  switch($person->get_mode()) {
	  case NORMAL_MODE:
		  echo "Showing normal-mode splash<BR>\n";
		  break;
	  case ADMIN_MODE:
		  echo "Showing admin-mode splash<BR>\n";
		  break;
	  default:
		  $code = create_pw(8);
		  error_output("Unknown mode, contact the administrator with this error code " . $code);
		  $msg  = $code . " ";
		  $msg .= "User " . $person->get_common_name() . " was given mode " . $person->get_mode();
		  $msg .= ". This is not a valid mode. Verify content in admins-table";
		  Logger::log_event(LOG_WARNING, $msg);
	  }
  } else {
	  include('unclassified_intro.php');
  }
} /* end keyhandle() */


/* sanitize_id
 *
 * Make sure that the id is an id an nothing more.
 */
function sanitize_id($id) {
     /* as PHP will fail to convert characters to an integer (will result in
      * '0'), this is a 'safe' test */
     return (int) htmlentities($id);
}
?>

