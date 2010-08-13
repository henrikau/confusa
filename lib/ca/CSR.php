<?php
require_once 'CryptoElement.php';
require_once 'Config.php';
require_once 'Output.php';
/**
 * CSR Class for handling signing requests
 *
 * The class should be able to handle CSRs in PEM or DER.
 *
 * @author Henrik Austad <henrik@austad.us>
 * @package ca
 */
abstract class CSR extends CryptoElement
{
	protected function getEncoding($elem)
	{
		$start = "CERTIFICATE REQUEST-----";
		$end   = "CERTIFICATE REQUEST-----";
		return parent::getEncoding($elem, $start, $end);
	}

	protected function der2pem($elem)
	{
		$start = "-----BEGIN CERTIFICATE-----\n";
		$end = "-----END CERTIFICATE-----\n";
		return parent::der2pem($elem, $start, $end);
	}

	protected function pem2der($elem)
	{
		$start = "REQUEST-----";
		$end   = "-----END";
		return parent::pem2der($elem, $start, $end);
	}

	/*
	 * --------------------------------------------------------------- *
	 *
	 *		Start of CSR specific functions.
	 *
	 * --------------------------------------------------------------- *
	 */


	public abstract function getAuthToken();

	public abstract function getCSRType();

	/**
	 * storeDB() store the CSR into the database
	 *
	 * @param	void
	 * @return	boolean True upon successfully storing the certificate
	 *			in the database
	 * @access	public
	 */
	public function storeDB($owner)
	{
		$insert  = "INSERT INTO csr_cache (csr, uploaded_date, common_name, auth_key, from_ip, type) ";
		$insert .= "VALUES(?,current_timestamp(),?,?,?,?)";
		$param   = array('text', 'text', 'text', 'text', 'text');
		$data	 = array($this->getPEMContent(),
				 $owner->getX509ValidCN(),
				 $this->getPubKeyHash(),
				 $_SERVER['REMOTE_ADDR'],
				 $this->getCSRType());
		try {
			MDB2Wrapper::update($insert, $param, $data);
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  " Coult not insert CSR into database. Server said: " .
					  $dbse->getMessage());
			return false;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  " Coult not insert CSR into database. Server said: " .
					  $dbqe->getMessage());
			return false;
		}
		return true;
	} /* end storeDB */

	public function setUploadedDate($date)
	{
		$this->date = $date;
	}
	public function getUploadedDate()
	{
		return $this->date;
	}
	public function getUploadedFromIP()
	{
		return $this->ip;
	}
	public function setUploadedFromIP($ip)
	{
		$this->ip = $ip;
	}


	/**
	 * getFromDB() find one (or all) CSR(s) for a person in the database.
	 *
	 * @param	uid		$person limit the query to the person's common-name
	 * @param	String|null	$pubHash the hash of the public key
	 * @return	CSR|False	The CSR for the person
	 * @access	public
	 */
	static function getFromDB($uid, $pubHash)
	{
		$res = false;
		if (!isset($uid) || !isset($pubHash)) {
			return false;
		}

		$query  = "SELECT * FROM csr_cache WHERE ";
		$query .= "auth_key=:auth_key AND ";
		$query .= "common_name=:common_name";

		$data = array();
		$data['auth_key']    = $pubHash;
		$data['common_name'] = $uid;

		try {
			$csr_res = MDB2Wrapper::execute($query, null, $data);
			if (count($csr_res) != 1) {
				return false;
			}
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  "cannot retrieve CSR from DB. Server said: " .
					  $dbse->getMessage());
			return false;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  "cannot retrieve CSR from DB. Server said: " .
					  $dbse->getMessage());
			return false;
		}

		$csr_type = $csr_res[0]['type'];

		if ($csr_type == CSR_PKCS10::getCSRType()) {
			$csr = new CSR_PKCS10($csr_res[0]['csr']);
		} else if ($csr_type == CSR_SPKAC::getCSRType()) {
			$csr = new CSR_SPKAC($csr_res[0]['csr']);
		} else {
			throw new CryptoElementException("Unsupported CSR type " .
			                                 $csr_type . "!");
		}

		$csr->setUploadedDate($csr_res[0]['uploaded_date']);
		$csr->setUploadedFromIP(Output::formatIP($csr_res[0]['from_ip'], true));

		if ($csr->getAuthToken() !== $pubHash) {
			Logger::log_event(LOG_ALERT, "Found CSR in database with hash $pubHash but ".
					  "this does not correspond to pubkey. Corrupted db?");
			return false;
		}
		return $csr;
	} /* end getFromDB() */


	/**
	 * listPErsonCSRs() get a list of a person's CSRs
	 *
	 * This will *not* return the CSRs, but the data stored around the
	 * CSRs. From this, it is trivial to retrieve the data from the
	 * database.
	 *
	 * Data stored in the array:
	 *
	 * - csr_id		The ID in the database.
	 * - uploaded_date	when the CSR was uploaded
	 * - common_name	Owner of the CSR
	 * - auth_key		Hash of the pubkey, used to retrieve a specific CSR
	 * - from_ip		The IP that sent the CSR.
	 *
	 * @param	String the x509Name stored as common-name in csr_cache
	 * @return	Array of CSR entries.
	 * @access	public
	 * @static
	 */
	static function listPersonCSRs($x509Name)
	{
		$query = "SELECT csr_id, uploaded_date, common_name, auth_key, from_ip".
			" FROM csr_cache WHERE common_name=:cn ORDER BY uploaded_date";
		try {
			$res = MDB2Wrapper::execute($query, null, array('cn' => $x509Name));
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  "cannot retrieve CSR from DB. Server said: " .
					  $dbse->getMessage());
			return false;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  "cannot retrieve CSR from DB. Server said: " .
					  $dbse->getMessage());
			return false;
		}
		return $res;
	}
	/**
	 * insertIntoDB() insert a CSR into the database (csr_cache)
	 *
	 * @param	CSR|String	$csr the CSR to store in the database
	 * @param	Person		$person the owner
	 * @return	Boolean		True if insertion went ok
	 * @access	public
	 */
	static function insertIntoDB($csr, $person)
	{
		if (is_string($csr)) {
			$csr = new CSR($csr);
			if ($csr->isVali()) {
				return $csr->storeDB();
			}
		}
		return false;
	} /* end insertIntoDB() */

	/**
	 * deleteFromDB() remove one (or all() CSR belonging to a person
	 *
	 * @param	Person		$person the owner of the CSR.
	 * @param	String|null	$pubHash optional hash. If present, only
	 *				this will be removed
	 * @return	Boolean		True if removed ok.
	 * @access	public
	 */
	static function deleteFromDB($person, $pubHash=null)
	{
		if (!isset($person)) {
			return false;
		}
		$remove = "DELETE FROM csr_cache WHERE common_name=:common_name";
		$data['common_name'] = $person->getX509ValidCN();
		if (isset($pubHash)) {
			$remove .= " AND auth_key=:auth_key";
			$data['auth_key'] = $pubHash;
		}
		try {
			MDB2Wrapper::update($remove, null, $data);
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  " Coult not remove CSR from database. Server said: " .
					  $dbse->getMessage());
			return false;
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
					  " Coult not remove CSR from database. Server said: " .
					  $dbqe->getMessage());
			return false;
		}
		return true;
	} /* end deleteFromDB() */
} /* end class CSR */
