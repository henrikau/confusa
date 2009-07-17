<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'person.php';
require_once 'send_element.php';

class RevokeCertificate extends FW_Content_Page
{
	/*
	 * CRL reason codes according to RFC 3280.
	 *
	 * Those having no real meaning for NREN and institution admins have
	 * been removed from this list.
	 */
	private $nren_reasons = array('unspecified',
				      'keyCompromise',
				      'affiliationChanged',
				      'superseeded',
				      'certificateHold',
				      'privilegeWithdrawn',
				      'aACompromise');

	function __construct()
	{
		parent::__construct("Revoke Certificate(s)", true);
	}

	function __destruct()
	{
		parent::__destruct();
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		if(isset($_GET['revoke'])) {
			switch($_GET['revoke']) {
				switch 'do_revoke':
				
				$this->revoke_certs($_POST['order_numbers'], $_POST['reason']);
				break;
			case 'do_revoke_list':
				$this->revoke_list($_POST['reason']);
				break;
			default:
				break;
			}
		}
	}
	public function process()
	{
		echo "<H3>Certificate Revocation Area</H3>\n";

		/* Determine if person is doing revoke in admin (for others) or
		 * normal (for himself) */
		if ($this->person->in_admin_mode())
			$this->admin_revoke();
		else
			$this->normal_revoke();
	}

	private function admin_revoke()
	{
		echo "Admin revoke<BR />\n";
		if (!$this->person->is_admin()) {
			Logger::log_event(LOG_ALERT, "User " . $person->get_valid_cn() . " allowed to set admin-mode, but is not admin");
			throw new ConfusaGenException("Impossible condition. NON-Admin user in admin-mode!");
		}

		/* Test access-rights */
		if (!$this->person->is_subscriber_admin() && !$this->person->is_subscriber_subadmin())
			throw new ConfusaGenException("Insufficient rights for revocation!");

		/* No need to do processing */
		if (!isset($_GET['revoke']))
			return;


		/* fields for retrieving certificates to retrieve. */
		$this->show_search_fields();

		/* Test for revoke-commands */
		switch($_GET['revoke']) {

			/* when we want so search for a particular certificate
			 * to revoke. */
		case 'search_display':
			$this->search_certs_display($_POST['search']);
			break;
                case 'search_list_display':
			$this->search_list_display('eppn_list', $person);
			break;

                default:
			echo "Unknown operation<BR />\n";
			break;
                }

	} /* end admin_revoke */

	private function normal_revoke()
	{
		echo "Normal revoke<BR />\n";
		$this->search_certs_display($this->person->get_valid_cn());
	}



	private function show_search_fields()
	{
		/* search for a particular certificate */
		set_value("search", "revoke_certificate.php?revoke=search_display", "Search");

		/* allow user to upload a CSV-list of certificates to revoke */
		if ($this->person->in_admin_mode()) {
			$file_name = "eppn_list";
			$target_url = $_SERVER['PHP_SELF'] . "?revoke=search_list_display";
			$submit_name = "Upload list";
			show_upload_form($target_url, $file_name, $submit_name = NULL);
		}
	} /* ened show_search_fields() */

	/**
	 * search_certs_display() - find and display a particular certificate
	 *
	 * Perform the search for a certain common name. Use the organization of the
	 * person as a search restriction. Display the result along with a revoke-
	 * option in a list grouped by different certificate owners.
	 *
	 * @param $common_name The common-name that is searched for. Will be automatically
	 *                     turned into a wildcard
	 * @param $person The person who is performing the search
	 */
	function search_certs_display($common_name)
	{
		$tr	="<div class=\"admin_table_row\">\n";
		$tr_e	= "</div>\n";

		$td	="<div class=\"admin_table_cell\">\n";
		$td_e	= "</div>\n";


		$common_name = "%" . sanitize($common_name) . "%";
		
		$certs = $this->certManager->get_cert_list_for_persons($common_name, $this->person->get_orgname());

		if (count($certs) > 0) {
			echo "<div class=\"admin_table\">\n";

			/* get the certificate owner/order number pairs into a ordering that
			 * permits us to send the order-numbers for each certificate owner
			 * to the revocation method */
			foreach($certs as $row) {
				$owners[] = $row['cert_owner'];
				$orders[$row['cert_owner']][] = $row['auth_key'];
			}

			$owners = array_unique($owners);

			echo $tr;
			echo "$td<b>Full Subject DN</b>$td_e$td<b>Revocation reason</b>$td_e$td$td_e";
			echo "$tr_e";

			foreach($owners as $owner) {
				echo "$tr";
				echo "$td";
				echo "<form action=\"?revoke=do_revoke\" method=\"POST\">";

				echo $owner . "\n";
				echo "$td_e$td";
				foreach ($orders[$owner] as $order) {
					echo "<input type=\"hidden\" name=\"order_numbers[]\" value=\"$order\" />";
				}

				$this->display_selectbox('unspecified', $this->nren_reasons, "reason");

				echo "$td_e$td";
				echo "<input type=\"submit\" name=\"submit\" value=\"Revoke All\"" .
					"onclick=\"return confirm('Are you sure?')\"/>";
				echo "$td_e";
				echo "</form>";
				echo $tr_e;
			}

			echo "</div>\n"; /* end table */
		}
	}

	/**
	 * Display a list of distinguished names whose certificates will be revoked
	 * based on an uploaded CSV with a list of eduPersonPrincipalNames. Offer the
	 * possibility to revoke these certificates.
	 *
	 * @param string $eppn_file The name of the $_FILES parameter containining the
	 *                          CSV of eduPersonPrincipalNames
	 * @param Person $person The person calling this function
	 *
	 * @throws FileException if something goes wrong in parsing the CSV file
	 */
	private function search_list_display($eppn_file)
	{
		/* These can become a *lot* of auth_keys/order_numbers. Thus, save the list
		 * of auth_keys preferrably in the session, otherwise it will take forever
		 * to download the site and I am not sure if it is such a good idea to send
		 * an endless list of auth_keys as hidden parameters
		 * to the user and then from there back again with a POST to the server
		 */
		if (isset($_SESSION['auth_keys'])) {
			unset($_SESSION['auth_keys']);
		}

		$csvl = new CSV_Lib($eppn_file);
		$eppn_list = $csvl->get_csv_entries();

		$certs = array();
		$auth_keys = array();

		foreach($eppn_list as $eppn) {
			$eppn = sanitize_eppn($eppn);
			$eppn = "%" . $eppn . "%";
			$eppn_certs = $this->certManager->get_cert_list_for_persons($eppn, $this->person->get_orgname());
			$certs = array_merge($certs, $eppn_certs);
		}

		if (count($certs) > 0) {
			echo "<b>The following DNs are going to be revoked:</b><br />\n";
			echo "<div class=\"spacer\"></div>";
			echo "<table class=\"small\">";
			/* get the certificate owner/order number pairs into a ordering that
			 * permits us to send the order-numbers for each certificate owner
			 * to the revocation method */
			foreach($certs as $row) {
				$owners[] = $row['cert_owner'];
				$auth_keys[] = $row['auth_key'];
			}

			$owners = array_unique($owners);

			foreach($owners as $owner) {
				echo "<tr style=\"width: 80%\"><td>";
				echo $owner;
				echo "</td></tr>";
			}
			echo "</table>";

			echo "<div class=\"spacer\"></div>";

			echo "<div style=\"text-align: right\">";
			echo "<form action=\"?revoke=do_revoke_list\" method=\"POST\">";
			echo "Revocation reason: ";
			display_selectbox('unspecfied', $this->nren_reasons, 'reason');
			$_SESSION['auth_keys'] = $auth_keys;
			echo "<input type=\"Submit\" value=\"Revoke all\"" .
				"onclick=\"return confirm('Are you sure?')\" />";
			echo "</form>";
			echo "</div>";
		}
	}

	private function display_selectbox($active, $choices, $sel_name)
	{
		echo "<select name=\"$sel_name\">\n";

		foreach($choices as $element) {
			if ($element == $active) {
				echo "<option value=\"$element\" selected=\"selected\">" . $element . "</option>\n";
			} else {
				echo "<option value=\"$element\">" . $element . "</option>\n";
			}
		}

		echo "</select>\n";
	}

}

$fw = new Framework(new RevokeCertificate());
$fw->start();

?>