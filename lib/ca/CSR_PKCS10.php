<?php
require_once 'CSR.php';
class CSR_PKCS10 extends CSR
{
	private $csr_pkcs10_pem;
	private $csr_pubkey_details;
	private $csr_pem;
	private $csr_der;

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
		} else {
			throw new CryptoElementException("Could not update details of this CSR. Probably the CSR could not get parsed.");
		}
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

	public function getAuthToken()
	{
		$pubkey = openssl_csr_get_public_key($this->csr_pem);
		if (!$pubkey) {
			return;
		}
		$keydata = openssl_pkey_get_details($pubkey);
		return sha1($keydata['key']);
	}

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

	public function getCSRType()
	{
		return "pkcs10";
	}
}
?>