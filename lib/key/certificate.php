<?php
require_once 'cert_lib.php';
require_once 'key_not_found.php';

class Certificate
{
	private $cert;
	private $fingerprint;
	private $serialNumber;
	private $availableFrom;
	private $owner;

	/* parsed (and cached data based on $parsed) */
	private $parsed;
	private $validUntil;

	function __construct($cert)
	{
		if (!isset($cert)) {
			throw new KeyNotFoundException("Cannot instansiate a certificate-object without a certificate.");
		}
		$this->cert = $cert;
		$this->parsed = openssl_x509_parse($this->cert);
		if (!isset($this->parsed) || $this->parsed == "") {
			$msg  = "Cannot instansiate a certificate-object with mangled data.<br />\n";
			$msg .= "The data received was:\n<pre>$cert</pre>\n";
			throw new KeyNotFoundException($msg);
		}
	}

	public function isValid()
	{
		if (isset($this->cert) && isset($this->parsed)) {
			/* expired? */
			$time = gettimeofday();
			if ($this->parsed['validTo_time_t'] < $time['sec']) {
				return false;
			}

			/* FIXME: need more tests? */
			return true;
		}
		return false;
	}

	public function setMadeAvailable($date)
	{
		$this->date = $date;
	}

	public function setOwner($owner)
	{
		$this->owner = $owner;
	}

	public function setComment($comment)
	{
		if (isset($comment) || $comment != "") {
			$this->comment = $comment;
		}
	}

	public function getComment()
	{
		if (!isset($this->comment)) {
			return "";
		}
		return $this->comment;
	}
	public function getCert($raw = true)
	{
		if (isset($this->cert) && $this->isValid()) {
			if ($raw) {
				return $this->cert;
			}
			openssl_x509_export($this->cert, $fullCert, true);
			return $fullCert;
		}
		return null;
	}

	/**
	 * getValidUntil() returns a string-represenation
	 *
	 * with valid until on the form 'YYYY-MM-DD HH:MM:SS'
	 *
	 */
	public function validTo()
	{
		if (!isset($this->valid_until)) {
			$this->valid_until = date("Y-m-d H:i:s", $this->parsed['validTo_time_t']);
		}
		return $this->valid_until;
	}

	public function fingerprint()
	{
		if (!isset($this->fingerprint)) {
			$this->fingerprint = openssl_x509_fingerprint($this->cert);
		}
		return $this->fingerprint;
	}

	public function serial()
	{
		if (!isset($this->serial)) {
			$this->serial = trim(openssl_x509_serial($this->cert));
		}
		return $this->serial;
	}

	public function getDN()
	{
		$dn = $this->parsed['name'];
		return $dn;
	}
	public function madeAvailable() {return $this->date; }

	public function getOwner() { return $this->owner; }

}
?>
