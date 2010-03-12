<?php
require_once 'CryptoElement.php';

/**
 * Certificate Class for handling certificate elements
 *
 * The class should be able to handle certificates in PEM or DER. Basic
 * operations should be supported, especially retrieving certificate attributes
 * and information like keylength, keytype etc.
 *
 * It stores the certificates in PEM-format, if DER is supplied, it will try to
 * convert to PEM.
 *
 * @author Henrik Austad <henrik@austad.us>
 * @package ca
 */
class Certificate extends CryptoElement
{

	private $x509;
	private $x509_pem;
	private $x509_der;
	private $x509_parsed;
	private $x509_pubkey_details;
	private $valid_from;
	private $valid_to;

	private $time_format = "Y-m-d H:i:s";
	/**
	 * __construct
	 *
	 * Initialize the Certificate object, store the certificate in PEM
	 * format (convert if necessary), read and parse the certificate.
	 *
	 * @param	String $content the supplied certificate in either PEM
	 *			or DER format
	 * @return	void
	 * @throws	CryptoElementException if the encoding is neither PEM
	 *			nor DER
	 * @access	public
	 */
	function __construct($content)
	{
		parent::__construct($content);
		$this->encoding = $this->getEncoding($content);
		switch($this->encoding) {
		case parent::$KEY_ENCODING_PEM:
			$this->x509_pem = $this->content;
			break;
		case parent::$KEY_ENCODING_DER:
			$this->x509_pem = $this->der2pem($this->content);
			$this->x509_der	= $this->content;
			break;
		default:
			throw new CryptoElementException("Internal problem, encoding set to non-recognizable format.");
		}
		$this->x509 = openssl_x509_read($this->x509_pem);
		$this->x509_parsed = openssl_X509_parse($this->x509);
	}

	/**
	 * __destruct() Unset the object
	 *
	 * @param	void
	 * @return	void
	 * @access	public
	 */
	function __destruct()
	{
		parent::__destruct();
		unset($this->x509);
		unset($this->x509_pem);
		unset($this->x509_der);
		unset($this->x509_parsed);
		unset($this->x509_pubkey_details);
		unset($this->valid_from);
		unset($this->valid_to);
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
		if (!is_null($this->x509_parsed) &&
		    array_key_exists('name', $this->x509_parsed)) {
			return $this->x509_parsed['name'];
		}
		return false;
	}

	/**
	 * @see CryptoElement::getLength()
	 */
	public function getLength()
	{
		if ($this->updateDetails('bits')) {
			return (int)$this->x509_pubkey_details['bits'];
		}
		return false;
	}

	/**
	 * @see CryptoElement::getType()
	 */
	public function getType()
	{
		if ($this->updateDetails('type')) {
			switch($this->x509_pubkey_details['type']) {
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
	 * @see CryptoElement::isValid()
	 */
	public function isValid()
	{
		/* if we were unable to import it as an X.509 certificate, it
		 * was malformed and as far as we're concerned, it is invalid */
		if (!$this->x509 || !is_array($this->x509_parsed)) {
			return false;
		}
		/* test dates, not expired, not too early */
		if (!array_key_exists('validFrom_time_t', $this->x509_parsed) ||
		    !array_key_exists('validTo_time_t', $this->x509_parsed)) {
			return false;
		}
		$now = gettimeofday();
		$now = $now['sec'];
		if ($now < $this->x509_parsed['validFrom_time_t']) {
			return false;
		}
		if ($now > $this->x509_parsed['validTo_time_t']) {
			return false;
		}

		/* could not find anything wrong */
		return true;
	}

	/**
	 * @see CryptoElement::getPEMContent()
	 */
	public function getPEMContent($raw=true)
	{
		if ($this->isValid()) {
			if ($raw) {
				return $this->x509_pem;
			}
			openssl_x509_export($this->x509_pem, $fullCert, true);
			return $fullCert;
		}
		return null;
	}

	/**
	 * @see CryptoElement::getDERContent()
	 */
	public function getDERContent($raw = true)
	{
		if (is_null($this->x509_der)) {
			$this->x509_der = $this->pem2der($this->content);
		}
		return $this->x509_der;
	}


	/*
	 * --------------------------------------------------------------- *
	 *
	 *		Start of Certificate specific functions.
	 *
	 * --------------------------------------------------------------- *
	 */


	/**
	 * getFingerprint()
	 *
	 * Get the certificate fingerprint. This is computed s the sha1sum of
	 * the DER-encoded certificate.
	 *
	 * @param	void
	 * @return	String the certificate fingerprint
	 * @access	public
	 */
	public function getFingerprint()
	{
		/* prints out the digest of the DER encoded version of the whole certificate (see digest options). */
		return chunk_split(strtoupper(sha1($this->getDERContent())), 2, ":");
	}


	/**
	 * getHash()
	 *
	 * Return the hash of the certificate. Often used by apache and other
	 * appliations to identify a certificate in a folder with many others.
	 *
	 * @param	void
	 * @return	String|false the hash of the public key, false if
	 *			something bad happene
	 * @access	public
	 */
	public function getHash()
	{
		if (is_array($this->x509_parsed) &&
		    array_key_exists('hash', $this->x509_parsed)) {
			return $this->x509_parsed['hash'];
		}
		return false;
	}


	/**
	 * getSerial() return the serial number in the certificate in hex-format
	 *
	 * @param	void
	 * @return	String|false the hex-encoded serial-number
	 * @access	public
	 */
	public function getSerial()
	{
		if (!is_null($this->x509_parsed) &&
		    array_key_exists('serialNumber', $this->x509_parsed)) {
			/* note the +0, this is needed to trigger correct
			 * handling of unsigned int in PHP when converting long strings. */
			return strtoupper(dechex($this->x509_parsed['serialNumber']+0));
		}
		return false;
	} /* end getSerial() */

	public function getPubKeyHash()
	{
		if ($this->updateDetails('key')) {
			return sha1($this->x509_pubkey_details['key']);
		}
		return false;
	}

	/**
	 * getBeginDate() return the date from when the certificate is valid.
	 *
	 * with valid until on the form 'YYYY-MM-DD HH:MM:SS' or on standard
	 *
	 */
	public function getBeginDate($hr = false)
	{
		if (!is_array($this->x509_parsed)) {
			return false;
		}
		if (!array_key_exists('validFrom_time_t', $this->x509_parsed)) {
			return false;
		}
		if (is_null($this->valid_from)) {
			$this->valid_from = date($this->time_format,
						 $this->x509_parsed['validFrom_time_t']);
		}
		if ($hr) {
			return date("r", $this->x509_parsed['validFrom_time_t']);
		}
		return $this->valid_from;
	}

	/**
	 * getEndDate() return the date when the certificate expires.
	 *
	 * @param	Boolean $hr Human readable format
	 * @return	String the date for when the certificate expires
	 * @access	public
	 */
	public function getEndDate($hr = false)
	{
		if (!is_array($this->x509_parsed)) {
			return false;
		}
		if (!array_key_exists('validTo_time_t', $this->x509_parsed)) {
			return false;
		}
		if (is_null($this->valid_to)) {
			$this->valid_to = date($this->time_format,
					       $this->x509_parsed['validTo_time_t']);
		}
		if ($hr) {
			return date("r", $this->x509_parsed['validTo_time_t']);
		}
		return $this->valid_to;
	}

	/**
	 * getStatus() return how the signing process of the certificate is going.
	 *
	 * This will interact with Cert_Manager and query for the certificate.
	 *
	 * @param	void
	 * @return	enum|false indicating status, false if something goes wrong.
	 * @access	public
	 */
	public function getStatus()
	{
		/* provide cert-manager with the certificate (self) and ask for
		 * status. CM will then retrieve the correct identifying
		 * attribute for the certificate and use that to query the
		 * backend database/API/whatever
		 */

		return false;
	}

	/**
	 * updateDetails() update the public-key details
	 *
	 * Make sure that the details are updated. It will also make sure that
	 * the supplied key is present, otherwise, false is returned.
	 *
	 * @param	String|null $key a key to test for
	 * @param	Boolean $force should the updated be forced through (if
	 *			content has changed).
	 *
	 */
	private function updateDetails($key=null, $force=false)
	{
		if (is_null($this->x509)) {
			return false;
		}

		/* should we update x509_pubkey_details? */
		if (is_null($this->x509_pubkey_details) ||
		    $force) {
			$pubkey = openssl_get_publickey($this->x509);
			if (!$pubkey) {
				return false;
			}
			$this->x509_pubkey_details = openssl_pkey_get_details($pubkey);
		}
		/* look for a specific key? */
		if (!is_null($key)) {
			return array_key_exists($key, $this->x509_pubkey_details);
		}
		return is_array($this->x509_pubkey_details);
	} /* End pubkeyDetails() */

	/**
	 * Convert a certificate in PEM-format to DER format
	 *
	 * @author	dan -AT- NOSPAM danschafer DOT netTT (http://php.net/manual/en/ref.openssl.php)
	 */
	private function pem2der($pem_data) {
		$begin = "CERTIFICATE-----";
		$end   = "-----END";
		$pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
		$pem_data = substr($pem_data, 0, strpos($pem_data, $end));
		return base64_decode($pem_data);
	}

	/**
	 * @see pem2der
	 */
	private function der2pem($der_data) {
		$pem = chunk_split(base64_encode($der_data), 64, "\n");
		$pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
		return $pem;
	}

	/**
	 * getEncoding() Determine the certificate encoding.
	 *
	 * The function is very simple. It assumes the certificate is valid, and
	 * the only valid alternatives are PEM and DER. If the certificate is
	 * not PEM, it is assumed to be DER.
	 *
	 * @param	void
	 * @return	int internal constant
	 * @access	private
	 */
	private function getEncoding($cert)
	{
		$start_pos = substr($cert, "-----BEGIN CERTIFICATE-----");
		$end_pos = substr($cert, "-----END CERTIFICATE-----");
		if ($start_pos && $end_pos) {
			return parent::$KEY_ENCODING_PEM;
		}
		return parent::$KEY_ENCODING_DER;
	}
} /* end Certificate */
?>
