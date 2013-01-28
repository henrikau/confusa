<?php
require_once "NREN.php";
require_once "Config.php";
require_once "MDB2Wrapper.php";

/** NRENAccount
 *
n * This class handles the account used for communication with (for now) Comodo
 *
 * It will read and write to the database, only updating values that have
 * changed, and handle new entries.
 *
 * It will not allow more than one account pr. NREN.
 *
 * mysql> desc account_map;
 * +----------------+--------------+------+-----+---------+----------------+
 * | Field          | Type         | Null | Key | Default | Extra          |
 * +----------------+--------------+------+-----+---------+----------------+
 * | account_map_id | int(11)      | NO   | PRI | NULL    | auto_increment |
 * | login_name     | varchar(128) | NO   |     | NULL    |                |
 * | password       | tinyblob     | NO   |     | NULL    |                |
 * | ivector        | tinyblob     | NO   |     | NULL    |                |
 * | ap_name        | varchar(30)  | NO   |     | NULL    |                |
 * | nren_id        | int(11)      | NO   | MUL | NULL    |                |
 * +----------------+--------------+------+-----+---------+----------------+
 */
class NRENAccount
{

	private static $NRENAccount = null;
	static function get($person)
	{
		if (is_null($person))
			return false;

		if (is_null(self::$NRENAccount))
			self::$NRENAccount = new NRENAccount($person);

		return self::$NRENAccount;
	}

	private $person;
	private $nren;
	private $login_name;
	private $password;
	private $ap_name;
	private $account_id;
	private $changed;

	private function __construct($person)
	{
		if (is_null($person))
			throw new ConfusaGenException("NRENAccount need person (to find NREN)");

		$this->person = $person;
		$this->nren = $this->person->getNREN();
		$this->changed = False;
		/* No need to run through this is NREN is not set */
		if ($this->nren && !$this->read()) {
			Logger::log_event(LOG_NOTICE,
							  "error reading account-data, probably because the NREN (" .
							  $this->nren->getID() .") does not have an account yet.");
		}
	}

	/*
	 * getters and setters for password, login_name and password.
	 */

	public function setPassword($pw)
	{
		if (!isset($pw) || $pw === "" || $pw === $this->password) {
			return false;
		}
		$this->password = $pw;
		$this->changed = true;
		return true;
	}

	public function getPassword($encode=false)
	{
		if(isset($this->password)) {
			if ($encode === true)
				return urlencode($this->password);
			return $this->password;
		}
		return false;
	}

	public function setLoginName($login_name)
	{
		if (!isset($login_name) || $login_name === "" || $login_name === $this->login_name)
			return false;
		$this->login_name = $login_name;
		$this->changed = true;
		return true;
	}

	public function getLoginName()
	{
		if (isset($this->login_name))
			return $this->login_name;
		return false;
	}

	public function setAPName($ap_name)
	{
		if (!isset($ap_name) || $ap_name === "" || $ap_name === $this->ap_name)
			return false;
		$this->ap_name = $ap_name;
		$this->changed = true;
		return true;
	}

	public function getAPName()
	{
		if (isset($this->ap_name))
			return $this->ap_name;
		return false;
	}

	/**
	 * read() get account-data from the database, decrypt the password
	 *
	 * This function will bail if the state is marked as 'changed'.
	 */
	public function read()
	{
		if (is_null($this->nren))
			return false;

		if ($this->changed) {
			Logger::log_event(LOG_ERR,
							  "Trying to read NREN-account whilst internal state is changed. Aborted.");
			return false;
		}

		$query  = "SELECT am.account_map_id, am.login_name, am.password, am.ivector, am.ap_name ";
		$query .= "FROM account_map am LEFT JOIN nrens n ON n.login_account = am.account_map_id ";
		$query .= "WHERE n.nren_id=?";

		/* FIXME:
		 * add internal state if in error
		 * use l10n
		 */
		try {
			$res = MDB2Wrapper::execute($query,
										array('integer'),
										array($this->nren->getID()));
		} catch (DBQueryException $dqe) {
			Logger::log_event(LOG_INFO,
							  "Could not determine the current ap_name and login_name for NREN " .
							  $this->nren->getID() .": " . $dqe->getMessage());
			return false;
		} catch (DBStatementException $dse) {
			Logger::log_event(LOG_INFO,
							  "Could not determine the current ap_name and login_name for NREN $nren: " .
							  $this->nren->getID() .": ". $dse->getMessage());
			return false;
		}
		if (count($res) == 1) {
			$this->parseAccountData($res, 0);
			return true;
		} else if (count($res) > 1) {
			Logger::log_event(LOG_ALERT,
							  "Too many account-results returned from DB for NREN " .
							  $this->nren->getID() .
							  ". This could indicate that the tables are corrupt (!)");
		}
		return false;
	} /* end read() */

	/**
	 * save() store updated results to the database, encrypting the password
	 * before storage.
	 */
	public function save($validate=true)
	{
		if (!$this->changed) {
			return false;
		}
		if ($validate && !(CAHandler::getCA($this->person)->verifyCredentials($this->login_name, $this->password))) {
			/* FIXME: l10n */
			throw new ConfusaGenException("Invalid username/password, Comodo will not accept!");
		}

		/* We create a new ivector every time we save the password */
		$size	= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
		$iv	= mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
		$cryptpw= base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
											   Config::get_config('capi_enc_pw'),
											   base64_encode($this->password),
											   MCRYPT_MODE_CFB,
											   $iv));
		if (isset($this->account_id)) {
			$sql    = "UPDATE account_map SET login_name=?, password=?, ivector=?, ap_name=?";
			$sql   .= " WHERE account_map_id = ?";
			$params = array('text', 'text', 'text', 'text', 'text');
			$data   = array($this->login_name, $cryptpw, base64_encode($iv), $this->ap_name, $this->account_id);

		} else {
			$sql    = "INSERT INTO account_map (login_name, password, ivector, ap_name, nren_id) ";
			$sql   .= "VALUES(?, ?, ?, ?, ?)";
			$params = array('text', 'text', 'text', 'text', 'integer');
			$data   = array($this->login_name, $cryptpw, base64_encode($iv), $this->ap_name, $this->nren->getID());
			/* Possible BUG: if we try to save() twice in a row for a new
			 * account, it will explode as it does not have the account_id */
		}

		try {
			MDB2Wrapper::update($sql, $params, $data);
			Logger::log_event(LOG_NOTICE, "account-data updated for NREN " . $this->nren->getID());
		} catch (DBQueryException $dqe) {
			$errorTag = PW::create();
			Logger::log_event(LOG_ERR,
							  "Could not update the login-account with ID " .
							  $this->account_id . " for " . $this->nren->getID() . "(" .
							  $this->login_name . ")");
			return false;
		} catch (DBStatementException $dse) {
			$errorTag = PW::create();
			Logger::log_event(LOG_ERR,
			                  "Could not update the login-account with ID " .
			                  $this->account_id . " to new value $login_name " . $dse->getMessage());
			return false;
		}
		$this->changed = false;
		return true;
	} /* end save() */

	private function parseAccountData($sql_res, $idx)
	{
		$this->login_name	= $sql_res[$idx]['login_name'];
		$this->ap_name		= $sql_res[$idx]['ap_name'];
		$this->account_id	= $sql_res[$idx]['account_map_id'];
		$this->password		= trim(base64_decode(mcrypt_decrypt(
													 MCRYPT_RIJNDAEL_256,
													 Config::get_config('capi_enc_pw'),
													 base64_decode($sql_res[$idx]['password']),
													 MCRYPT_MODE_CFB,
													 base64_decode($sql_res[$idx]['ivector']))));

	}
}
?>
