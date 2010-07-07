<?php
require_once 'CryptoElement.php';
require_once 'Config.php';
/**
 * CSR Class for handling signing requests
 *
 * The class should be able to handle CSRs in PEM or DER.
 *
 * @author Henrik Austad <henrik@austad.us>
 * @package ca
 */
class CSR extends CryptoElement
{
	private $csr_pem;
	private $csr_der;
	private $csr_pubkey_details;

	function __construct($content)
	{
		parent::__construct($content);
		$this->encoding = $this->getEncoding($content);
		switch($this->encoding) {
		case parent::$KEY_ENCODING_PEM:
			$this->csr_pem = $this->content;
			break;
		case parent::$KEY_ENCODING_DER:
			$this->csr_pem = $this->der2pem($this->content);
			$this->csr_der	= $this->content;
			break;
		default:
			throw new CryptoElementException("Internal problem, encoding set to non-recognizable format.");
		}
	}
	function __toString()
	{
		return $this->getPEMContent();
	}


	/*
	 * --------------------------------------------------------------- *
	 *
	 *		Start of requried functions from CryptoElement.
	 *
	 * --------------------------------------------------------------- *
	 */

	/**
	 * @see CryptoElement::getSubject()
	 */
	public function getSubject()
	{
		$sa = openssl_csr_get_subject($this->content);
		$res=false;
		foreach($sa as $key => $value) {
			$res.="/$key=$value";
		}
		return $res;
	}

	/**
	 * @see CryptoElement::getLength()
	 */
	public function getLength()
	{
		if ($this->updateDetails('bits')) {
			return $this->csr_pubkey_details['bits'];
		}
		return false;
	}

	/**
	 * @see CryptoElement::getType()
	 */
	public function getType()
	{
		if ($this->updateDetails('type')) {
			switch($this->csr_pubkey_details['type']) {
			case OPENSSL_KEYTYPE_RSA:
				return "rsa";
			case OPENSSL_KEYTYPE_DSA:
				return "dsa";
			case OPENSSL_KEYTYPE_DH:
				return "dh";
			case OPENSSL_KEYTYPE_EC:
				return "ec";
			}
		}
		return false;
	}

	/**
	 * @see CryptoElement::getPubKeyHash()
	 */
	public function getPubKeyHash()
	{
		if ($this->updateDetails('key')) {
			return sha1($this->csr_pubkey_details['key']);
		}
		return false;
	}

	/**
	 * @see CryptoElement::isValid()
	 *
	 * Test for length and keytype.
	 */
	public function isValid()
	{
		if ($this->getLength() < Config::get_config('key_length')) {
			return false;
		}
		if ($this->getType() != "rsa") {
			return false;
		}
		return true;
	}

	/**
	 * @see CryptoElement::getPEMContent()
	 */
	public function getPEMContent($raw = true)
	{
		return $this->csr_pem;
	}

	/**
	 * @see CryptoElement::getDERContent()
	 */
	public function getDERContent($raw = true)
	{
		if (!isset($this->csr_der)) {
			$this->csr_der = $this->pem2der($this->csr_pem, 'csr');
		}
		return $this->csr_der;
	}


	/*
	 * --------------------------------------------------------------- *
	 *
	 *		Start of CSR specific functions.
	 *
	 * --------------------------------------------------------------- *
	 */

	/**
	 * updateDetails() scan the pubkey and retrieve key-specific details
	 *
	 * @param	String|null	a specific element to look for in the details
	 * @param	Boolean		force a reparsing of the pubkey
	 * @return	Boolean		true if key was found or if the key has
	 *				been parsed.
	 * @access	private
	 */
	private function updateDetails($key=null, $force=false)
	{
		if (is_null($this->csr_pubkey_details) || $force) {
			$pubkey = openssl_csr_get_public_key($this->csr_pem);
			if (!$pubkey) {
				return false;
			}
			$this->csr_pubkey_details = openssl_pkey_get_details($pubkey);
		}
		if (!is_null($key)) {
			return array_key_exists($key, $this->csr_pubkey_details);
		}
		return is_array($this->csr_pubkey_details);
	} /* end updateDetails() */

	/**
	 * getFromDB() find one (or all) CSR(s) for a person in the database.
	 *
	 * @param	uid		$person limit the query to the person's common-name
	 * @param	String|null	$pubHash the hash of the public key
	 * @return	CSR|False	The CSR for the person
	 * @access	public
	 */
	static function getFromDB($uid, $pubHash=null)
	{
		$res = false;
		if (!isset($uid)) {
			return false;
		}
		$query  = "SELECT * FROM csr_cache WHERE ";
		$query .= "auth_key=:auth_key AND ";
		$query .= "common_name=:common_name";

		$data = array();
		$data['auth_key'] = $pubHash;
		$data['common_name'] = $uid;

		try {
			$csr_res = MDB2Wrapper::execute($query, null, $data);
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
		return new CSR($csr_res[0]['csr']);
	} /* end getFromDB() */

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
		}
		if (!$csr->isValid()) {
			return false;
		}
		$insert  = "INSERT INTO csr_cache (csr, uploaded_date, common_name, auth_key) ";
		$insert .= "VALUES(?,current_timestamp(),?,?)";
		$param   = array('text', 'text', 'text');
		$data	 = array($csr->getPEMContent(),
				 $person->getX509ValidCN(),
				 $csr->getPubKeyHash());
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
} /* end class CSR */
