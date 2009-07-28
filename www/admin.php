<?php
require_once 'confusa_include.php';
include_once 'framework.php';
include_once 'mdb2_wrapper.php';
include_once 'db_query.php';
include_once 'logger.php';


/**
 * Admin - administer admins for the subscriber/nren
 *
 * Each NREN has a set of NREN-admins and subscriber-admins. Each subscriber may
 * manage its own subuscriber admins and subadmin.
 */
class CP_Admin extends FW_Content_Page
{
	private $org_states;
	private $org_name_cache;
	private $urls;
	function __construct()
	{
		parent::__construct("Admin", true);
		$this->org_states		= array('','subscribed', 'suspended', 'unsubscribed');
		$this->org_name_cache		= array();

		/* Create the urls  */
		$this->urls			= array();
		$this->urls['subscribers']	= Output::create_link($_SERVER['SCRIPT_NAME'] . "?subscribe",	"Subscribers");
		$this->urls['accounts']		= Output::create_link($_SERVER['SCRIPT_NAME'] . "?account",	"Accounts");
		$this->urls['nren']		= Output::create_link($_SERVER['SCRIPT_NAME'] . "?nren",	"NREN");
	}

	public function pre_process($person)
	{
		parent::pre_process($person);

		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->is_subscriber_admin() || $this->person->is_nren_admin()))
			return false;

		/* test for flags */
		if (isset($_GET['admin'])) {
			switch(htmlentities($_GET['admin'])) {
			default:
				break;
			}
		}
	}

	/*
	 * Direct the user to the respective operations she may perform
	 * Currently the admin page can manage NREN subscriptions,
	 * organization subscriptions and (Comodo) subaccounts.
	 *
	 * The post parameters that are passed are supposed to be $this->sanitized in
	 * the respective functions that take them
	 */
	public function process()
	{
		/* IF user is not subscirber- or nren-admin, we stop here */
		if (!($this->person->is_subscriber_admin() || $this->person->is_nren_admin())) {
			echo "<H3>Not Authorized for this action</H3>\n";
			Logger::log_event(LOG_NOTICE, "User " . $this->person->get_valid_cn() . " was rejected at the admin-interface");
			$this->tpl->assign('reason', 'You do not have sufficient rights to view this page');
			$this->tpl->assign('content', 'restricted_access.tpl');
			return false;
		}
		$this->tpl->assign('link_urls', $this->urls);
		$this->tpl->assign('content', $this->tpl->fetch('admin.tpl'));
	}
	

	private function handle_account_actions($action)
	{

		switch($action) {

		case 'add':
			$this->add_account($_POST['login_name'], $_POST['login_password']);
			$this->show_accounts_mask();
			break;

		case 'delete':
			$this->delete_account($_POST['login_name']);
			$this->show_accounts_mask();
			break;

		case 'edit':
			$this->edit_account($_POST['login_name'], $_POST['login_password']);
			$this->show_accounts_mask();
			break;

		case 'manage':
		default:
			$this->show_accounts_mask();
			break;
		}

	}

	/*
	 * Edit the subscription status or the associated subaccount of the
	 * organization passed..
	 * Check the received organization state or subaccount-name carefully
	 * for their feasabiltiy before performing an update (i.e. check if they
	 * are already in the database without passing them to SQL).
	 */
	private function edit_subscriptions($org, $new_org_state, $new_nren_name)
	{
		/* check if we have to organization in the DB. Thus we make sure
		 * the sent post-data is sane and not malicious
		 * Also check all other input values for sanity.
		 */
		if (array_search($org, $this->getOrganizationNames()) === FALSE) {
			throw new ConfusaGenException("The organization $org you were about to edit " .
						      "is unknown!");
		} else if (array_search($new_org_state, $this->org_states) === FALSE) {
			throw new ConfusaGenException("Tried to update organization state " .
						      "to an unknown value!"
				);
		} else if (array_search($new_nren_name, $this->getNRENNames()) === FALSE) {
			throw new ConfusaGenException("Tried to update login name " .
						      "to an unknown value!"
				);
		} else {

			/* get the right nren-id first. subselects are expensive */
			$res = MDB2Wrapper::execute("SELECT nren_id FROM nrens " .
						    "WHERE name = ?",
						    array('text'),
						    $new_nren_name
				);

			if (count($res) != 1) {
				throw new DBQueryException("Could not retrieve a valid nren_id for " .
							   "nren name $new_nren_name"
					);
			}

			$nren_id = $res[0]['nren_id'];
			$stmt = "UPDATE subscribers SET org_state = ?, nren_id = ? " .
				"WHERE name = ?";
			MDB2Wrapper::update($stmt, array('text','text', 'text'),
					    array($new_org_state, $nren_id ,$org)
				);

			Logger::log_event(LOG_INFO, "Changed organization $org to state " .
					  "$new_org_state and nren $new_nren_name.\n");
		}
	}

	/*
	 * Edit an existing account. This only refers to updating the encrypted
	 * password entry. If the name of the account is to change as well, the
	 * user is advised to just delete the account and re-create it
	 */
	private function edit_account($login_name, $login_pw) {
		$login_name = $this->sanitize($login_name);
		$login_pw = base64_encode($login_pw);

		$enckey = Config::get_config('capi_enc_pw');
		$size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
		$iv=mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

		$cryptpw = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$enckey,$login_pw,
							MCRYPT_MODE_CFB, $iv
						 ));

		$query = "UPDATE account_map SET password=?,ivector=? " .
			"WHERE login_name=?";

		MDB2Wrapper::update($query,array('text','text','text'),
				    array($cryptpw, base64_encode($iv), $login_name));

		Logger::log_event(LOG_INFO, "Updated the password of account " .
				  "$login_name\n"
			);

	}

	/*
	 * Edit an existing NREN. I.E. change the (Comodo) subaccount which
	 * is associated with it
	 */
	private function edit_nren($nren_name, $login_name) {
		$nren_name = $this->sanitize($nren_name);
		$login_name = $this->sanitize($login_name);

		$map_id_query = "SELECT account_map_id FROM account_map WHERE login_name=?";

		$res = MDB2Wrapper::execute($map_id_query, array('text'), $login_name);

		if (count($res) != 1) {
			throw new DBQueryException("Could not find the account map ID for " .
						   "login-name $login_name\n"
				);
		}

		$map_id = $res[0]['account_map_id'];

		$update_query = "UPDATE nrens SET login_account=? WHERE name=?";
		MDB2Wrapper::update($update_query, array('text','text'),
				    array($map_id, $nren_name));

		Logger::log_event(LOG_INFO, "Updated NREN $nren_name to use new " .
				  "account $map_id\n");
	}

	/*
	 * Show a mask permitting the user to add, update and delete remote-CA
	 * subaccounts.
	 */
	private function show_accounts_mask() {
		$table = "<div class=\"admin_table\">\n";
		$tr = "<div class=\"admin_table_row\">\n";
		$td = "<div class=\"admin_table_cell\">\n";
		$td_e="</div>\n";
		$tr_e = "</div>\n";
		$table_e = "</div>\n";

		$query = "SELECT login_name, password, ivector FROM account_map a ";


		echo $table;
		echo $tr;
		echo "$td$td_e$td<b>Login name</b>$td_e$td<b>Password</b>$td_e$td$td_e";
		echo $tr_e;
		$res = MDB2Wrapper::execute($query, NULL, NULL);

		if (count($res) >= 1) {

			foreach($res as $row) {
				$lname = $row['login_name'];
				echo $tr;
				echo $td;
				echo "<form action=\"?account=delete\" method=\"POST\">\n";
				echo "<input type=\"hidden\" name=\"login_name\" value=\"$lname\" />\n";
				echo "<input type=\"image\" name=\"delete\" " .
					"onclick=\"return confirm('Delete entry?')\" " .
					"value=\"delete\" src=\"graphics/delete.png\" alt=\"delete\" />\n";
				echo "</form>\n";
				echo $td_e;
				echo $td;
				echo "<form action=\"?account=edit\" method=\"POST\">\n";
				echo $lname;
				echo $td_e;
				echo $td;
				$pw =  trim(base64_decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,
									 Config::get_config('capi_enc_pw'), base64_decode($row['password']),
									 MCRYPT_MODE_CFB,base64_decode($row['ivector']))));

				echo "<input type=\"password\" name=\"login_password\" value=\"$pw\" />\n";
				echo $td_e;
				echo $td;
				echo "<input type=\"hidden\" name=\"login_name\" value=\"$lname\" />";
				echo "<input type=\"submit\" name=\"submit\" value=\"Update\" />";
				echo $td_e;
				echo "</form>";
				echo $tr_e;
			}
		}

		echo "<div class=\"spacer\"></div>";
		echo $tr;
		echo $td . $td_e;
		echo $td;
		echo "<form action=\"?account=add\" method=\"POST\">\n";
		echo "<input type=\"text\" name=\"login_name\" />\n";
		echo $td_e;
		echo $td;
		echo "<input type=\"password\" name=\"login_password\" />\n";
		echo $td_e;
		echo $td;
		echo "<input type=\"submit\" name=\"submit\" value=\"Add new\" />\n";
		echo $td_e;
		echo "</form>";
		echo $tr_e;
		echo $table_e;
	}

	/*
	 * Show a mask permitting the user to add, delete and update subscribed
	 * NRENs
	 */
	private function show_nrens_mask()
	{
		$table	= "<div class=\"admin_table\">\n";
		$tr	= "<div class=\"admin_table_row\">\n";
		$td	= "<div class=\"admin_table_cell\">\n";
		$td_e	="</div>\n";
		$tr_e	= "</div>\n";
		$table_e= "</div>\n";

		$query	= "SELECT nren, account_login_name FROM nren_account_map_view";

		$res = MDB2Wrapper::execute($query, NULL, NULL);

		echo $table;
		echo $tr;
		echo "$td$td_e$td<b>NREN</b>$td_e$td<b>Account</b>$td_e$td$td_e";
		echo $tr_e;

		if (count($res) >= 1) {
			foreach($res as $row)  {
				$cur_nren = $row['nren'];
				echo $tr;
				echo $td;
				echo "<form action=\"?nren=delete\" method=\"POST\">\n";
				echo "<input type=\"hidden\" name=\"nren_name\" value=\"$cur_nren\" />\n";
				echo "<input type=\"image\" name=\"delete\" " .
					"onclick=\"return confirm('Delete entry?')\" " .
					"value=\"delete\" src=\"graphics/delete.png\" alt=\"delete\" />\n";
				echo "</form>\n";
				echo $td_e;
				echo $td;
				echo "<form action=\"?nren=edit\" method=\"POST\">\n";
				echo $cur_nren;
				echo $td_e;
				echo $td;
				$this->displaySelectBox($row['account_login_name'], $this->getLoginNames(), 'login_name');
				echo $td_e;
				echo $td;
				echo "<input type=\"hidden\" name=\"nren_name\" value=\"$cur_nren\" />\n";
				echo "<input type=\"submit\" name=\"submit\" value=\"Update\" />\n";
				echo $td_e;
				echo "</form>\n";
				echo $tr_e;
			}
		}

		echo "<div class=\"spacer\"></div>";

		/* Add new subscriber to the NREN */
		echo $tr;

		echo $td . $td_e;

		echo $td;
		echo "<form action=\"?nren=add\" method=\"POST\">\n";
		echo "<input type=\"text\" name=\"nren_name\" />\n";
		echo $td_e;

		echo $td;
		$this->displaySelectBox('subscribed',$this->getLoginNames(),'login_name');
		echo $td_e;

		echo $td;
		echo "<input type=\"submit\" name=\"add\" value=\"Add new\" />\n";
		echo $td_e;

		echo "</form>\n";
		echo $tr_e;
		echo $table_e;
	}

	/*
	 * Add remote-CA subaccount information to the system.
	 * The password will be stored in the DB encrypted and due to MySQL
	 * encryption deficiencies it will be encrypted in the application layer,
	 * i.e. here. The initialization vector IV for the used block mode is
	 * stored along with the encrypted data, since it needs not be secret.
	 * Thus the data can be easily decrypted again */
	private function add_account($login_name, $login_password)
	{
		$login_name = $this->sanitize($login_name);
		/* this will get encrypted before insertion to the database, so it
		 * does not need to be $this->sanitized. However, base64_encode allows the
		 * user to include all kinds of special characters in the password */
		$login_password = base64_encode($login_password);
		$enckey = Config::get_config('capi_enc_pw');

		$size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
		$iv=mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

		/*
		 * It may seem overblown to base64-encode first the password and then
		 * the encryption result again. But testing revealed that
		 * insertion can fail sometimes if the encryption string is not b64-encoded.
		 * It doesn't take very long to b64-encode and it makes inserting the
		 * encrypted account into the DB safe.
		 */
		$cryptpw = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$enckey,
							$login_password, MCRYPT_MODE_CFB, $iv
						 ));

		$ivector = base64_encode($iv);
		$query="INSERT INTO account_map(login_name, password, ivector)" .
			"VALUES(?, ?, ?)";

		MDB2Wrapper::update($query, array('text','text','text'),
				    array($login_name, $cryptpw, $ivector));

		Logger::log_event(LOG_INFO, "Inserted new account $login_name into " .
				  "account-map\n"
			);
	}

	/*
	 * Add a NREN. A NREN usually has a name and an associated login-sub-
	 * account.
	 */
	private function addNREN($nren_name, $login_name) {
		$nren = $this->sanitize($nren_name);
		$account = $this->sanitize($login_name);

		$map_id_query = "SELECT account_map_id FROM account_map WHERE login_name=?";

		$res = MDB2Wrapper::execute($map_id_query, array('text'),
					    array($account));
		$map_id = NULL;

		if (count($res) == 1) {
			$map_id = $res[0]['account_map_id'];
		}

		$query = "INSERT INTO nrens(name, login_account) VALUES(?, ?)";

		MDB2Wrapper::update($query, array('text','text'), array($nren, $map_id));

		Logger::log_event(LOG_INFO, "Added NREN with name $nren_name and login " .
				  "$login_name to the DB\n"
			);
	}

	/*
	 * Delete a login subaccount. Will not delete connected NRENs, but set
	 * their respective table column NULL.
	 */
	private function delete_account($login_name) {
		$login_name = $this->sanitize($login_name);
		MDB2Wrapper::execute("DELETE FROM account_map WHERE login_name = ?",
				     array('text'),
				     $login_name
			);

		Logger::log_event(LOG_INFO, "Delete account $login_name.\n");
	}

	/*
	 * Delete a NREN.
	 * Notice that this can also delete a lot of institutions due to the
	 * ON DELETE CASCADE constraint
	 */
	private function delete_nren($nren_name) {
		$nren_name = $this->sanitize($nren_name);
		MDB2Wrapper::execute("DELETE FROM nrens WHERE name=?",
				     array('text'),
				     $nren_name
			);

		Logger::log_event(LOG_INFO, "Delete NREN $nren_name.\n");
	}

	/**
	 * display a select-box with the elements in choices as the alternative
	 * highlight the element passed as 'active'
	 */
	private function displaySelectBox($active, $choices, $sel_name)
	{
		echo "<select name=\"$sel_name\">\n";
		foreach($choices as $element) {
			if ($element !== "") {
				if ($element == $active) {
					echo "<option value=\"$element\" selected=\"selected\">" . $element . "</option>\n";
				} else {
					echo "<option value=\"$element\">" . $element . "</option>\n";
				}
			}
		}

		echo "</select>\n";
	}

	/*
	 * Return a list of all the nren names.
	 */
	private function getNRENNames()
	{
		$nren_names=array();
		$query = "SELECT name FROM nrens";
		$res = MDB2Wrapper::execute($query, NULL, NULL);

		foreach($res as $row) {
			$nren_names[] = $row['name'];
		}

		return $nren_names;
	}

	/*
	 * Return a list of all signed-up institutions.
	 */
	private function getOrganizationNames()
	{
		$org_names=array();
		$query = "SELECT name FROM subscribers";
		$res = MDB2Wrapper::execute($query, NULL, NULL);

		foreach($res as $row) {
			$org_names[] = $row['name'];
		}

		return $org_names;
	}

	/*
	 * Return a list of all stored login-subaccounts
	 */
	private function getLoginNames()
	{
		$login_names=array();
		$query = "SELECT login_name FROM account_map";
		$res = MDB2Wrapper::execute($query, NULL, NULL);

		foreach($res as $row) {
			$login_names[] = $row['login_name'];
		}

		return $login_names;
	}



}

$fw = new Framework(new CP_Admin());
$fw->start();


?>

