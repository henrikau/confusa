<?php
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'logger.php';
require_once 'csr_lib.php';
require_once 'upload_form.php';
require_once 'file_upload.php';
require_once 'config.php';

$fw = new Framework('process_csr');
$fw->force_login();
$fw->render_page();

function process_csr($person)
{
	echo "<H3>Requesting new Certificates</H3>\n";
	/* show upload-form. If it returns false, no uploaded CSRs were processed */
	process_file_csr($person);

	/* if flags are set, process the CSRs */
	if (process_csr_flags_set())
		process_db_csrs($person);


	if (Config::get_config('debug')) {
		list_all_csr($person);
	}
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
function process_file_csr($person)
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
				$ip=$_SERVER['REMOTE_ADDR'];
				$query  = "INSERT INTO csr_cache (csr, uploaded_date, from_ip,";
				$query .= " common_name, auth_key)";
				$query .= " VALUES(?, current_timestamp(), ?, ?, ?)";
				MDB2Wrapper::update($query,
						    array('text', 'text', 'text', 'text'),
						    array($csr, $ip, $person->get_common_name(), $authvar));
				$logmsg  = __FILE__ . " Inserted new CSR from $ip (" . $person->get_common_name();
				$logmsg .=") with hash " . pubkey_hash($csr, true);
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
}

function process_csr_flags($person)
{
	decho("Processing CSRs");
}

function list_all_csr($person)
{
	$query = "SELECT csr_id, uploaded_date, common_name, auth_key, from_ip FROM csr_cache WHERE common_name=?";
	$res = MDB2Wrapper::execute($query,
				    array('text'),
				    $person->get_valid_cn());
	if (count($res) > 0) {
		/* Handle each separate instance */
		echo "<TABLE CLASS=\"small\">\n";
		echo "<TR>";
		echo "<TH>Database ID</TH>";
		echo "<TH>Uploaded date</TH>";
		echo "<TH>Common Name</TH>";
		echo "<TH>From IP</TH>";
		echo "<TH>Inspect</TH>";
		echo "<TH>Delete</TH>";
		echo "</tr>\n";
		foreach ($res as $key => $value) {
			echo "<TR>";
			echo "<TD>"	. $value['csr_id'] . "</TD>\n";
			echo "<TD>"	. $value['uploaded_date'] . "</TD>\n";
			echo "<TD><I>"	. $value['common_name'] . "</I></TD>\n";

			echo "<TD>";
			if ($_SERVER['REMOTE_ADDR'] != $value['from_ip']) {
				$diff = true;
			}
			if ($diff)
				echo "<FONT COLOR=\"RED\"><B><I>";
			echo $value['from_ip'] . "</TD>\n";
			if ($diff)
				echo "</I></B></FONT>";

			echo "<TD>[ <A HREF=\""	.$_SERVER['PHP_SELF']."?inspect_csr=".$value['auth_key']."\">Inspect</A> ]</TD>\n";
			echo "<TD>[ <A HREF=\""	.$_SERVER['PHP_SELF']."?delete_csr=".$value['auth_key']."\">Delete</A> ]</TD>\n";
			echo "</tr>\n";
		}
		echo "</TABLE>\n";
	} else {
		decho("There are no valid CSRs currently stored in the database for " . $person->get_common_name());
	}
}

?>