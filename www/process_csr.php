<?php
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'logger.php';
require_once 'csr_lib.php';
require_once 'upload_form.php';
require_once 'file_upload.php';

$person = null;
$fw = new Framework('process_csr');
$fw->force_login();
$fw->render_page();

function process_csr($person)
{
	echo "<H3>Requesting new Certificates</H3>\n";
	/* show upload-form. If it returns false, no uploaded CSRs were processed */
	process_file_csr();
}


function process_csr_flags_set()
{
	return isset($_GET['delete_csr']) || isset($_GET['auth_token']) || isset($_GET['inspect_csr']);
}


/**
 * process_file_csr - walk an uploaded CSR through the steps towards a certificate
 *
 * If a new CSR has been uploaded via FILE, this will retrieve it, store it in
 * the database and pass control over to CertManager to process it. 
 */
function process_file_csr()
{
	global $fw;
	$cm = $fw->get_cert_manager();

	/* Testing for uploaded files */
	if(isset($_FILES['user_csr']['name'])) {
		decho("Found new CSR<BR>\n");
		$fu = new FileUpload('user_csr', true, 'test_content');
		if ($fu->file_ok()) {
			decho("File-upload OK, starting test");
			$csr = $fu->get_content();
			$authvar = pubkey_hash($fu->get_content(), true);
			

			/* are the CSR already uploaded? */
			$res = MDB2Wrapper::execute("SELECT auth_key, from_ip FROM csr_cache WHERE csr=?",
						    array('text'),
						    array($csr));
			if (count($res)>0) {
				error_output("CSR already present in the database, no need for second upload");
			} else {
				decho("Inserting into system");
				$query  = "INSERT INTO csr_cache (csr, uploaded_date, from_ip,";
				$query .= " common_name, auth_key)";
				$query .= " VALUES(?, current_timestamp(), ?, ?, ?)";
				MDB2Wrapper::update($query,
						    array('text', 'text', 'text', 'text'),
						    array($csr, $ip, $common, $auth_var));
				$logmsg  = __FILE__ . " Inserted new CSR from $ip ($common) with hash " . pubkey_hash($csr, true);
				Logger::log_event(LOG_INFO, $logmsg);
			}
			/* CertManager will test content of CSR before sending it off for signing
                         *
                         * As we upload the key manually, the user-script won't
                         * be called for creating a auth-token. We therefore
                         * create a random string containing the correct amount
                         * of characters. It will contain more letters than the
                         * user-script (which uses sha1sum of some random text).
                         */
		} else {
			error_output("There were errors encountered when processing the file.");
			error_output("Please create a new keypair and upload a new CSR to the server.");
		}
	}
	show_upload_form($_SERVER['PHP_SELF']);
        return false;
}

?>