<?php
require_once 'CryptoElement.php';

/**
 * CRL - Wrapper class for Revocation Lists
 *
 */
class CRL extends CryptoElement
{
	private $crl_pem;
	private $crl_der;
	function __construct($content)
	{
		parent::__construct($content);
		$this->encoding = $this->getEncoding($content);

		switch($this->encoding) {
		case parent::$KEY_ENCODING_PEM:
			$this->crl_pem = $this->content;
			break;
		case parent::$KEY_ENCODING_DER:
			$this->crl_pem = $this->der2pem($this->content);
			$this->crl_der	= $this->content;
			break;
		default:
			throw new CryptoElementException("Internal problem, encoding set to non-recognizable format.");
		}
	}

	/**
	 * getSubject() return the issuer of the CRL
	 *
	 * This is as close as we're going to get to the 'subject' in a CRL.
	 *
	 * @param	void
	 * @return	String|false the issuer of the CRL
	 * @access	public
	 */
	public function getSubject()
	{
		if (is_null($this->issuer)) {
			$cmd ="echo \"" . $this->crl_pem . "\" | openssl crl -issuer -noout";
			$this->issuer = shell_exec($cmd);
			$this->issuer = trim(preg_replace('/^issuer=/','',$this->issuer), "\n");
		}
		return $this->issuer;
	}

	public function getLength()
	{
		return false;
	}

	public function getType()
	{
		/* FIXME, assuming that all CRLs use sha1+RSA */
		return "rsa";
	}
	public function getPubKeyHash()
	{
		if (is_null($this->hash)) {
			$cmd ="echo \"" . $this->crl_pem . "\" | openssl crl -hash -noout";
			$this->hash = shell_exec($cmd);
		}
		return $this->hash;
	}

	public function isValid()
	{
		$res  = shell_exec("echo \"" . $this->crl_pem . "\" | openssl crl -lastupdate -noout");
		$last = strtotime(substr($res, strpos($res, "=")+1));
		$res  = shell_exec("echo \"" . $this->crl_pem . "\" | openssl crl -nextupdate -noout");
		$next = strtotime(substr($res, strpos($res, "=")+1));
		$now  = time();
		return $now > $last && $now < $next;
	}

	public function getPEMContent($raw = true)
	{
		if (!$raw) {
			$cmd ="echo \"" . $this->crl_pem . "\" | openssl crl -text -noout";
			return trim(shell_exec($cmd), "\n");
		}
		return $this->crl_pem;
	}

	public function getDERContent($raw = true)
	{
		if (!isset($this->crl_der)) {
			$this->crl_der = $this->pem2der($this->crl_pem);
		}
		return $this->crl_der;
	}

	protected function getEncoding($elem)
	{
		$start = "X509 CRL-----";
		$end   = "X509 CRL-----";
		return parent::getEncoding($elem, $start, $end);
	}

	protected function der2pem($elem)
	{
		$start = "-----BEGIN X509 CRL-----\n";
		$end = "-----END X509 CRL-----\n";
		return parent::der2pem($elem, $start, $end);
	}

	protected function pem2der($elem)
	{
		$start = "CRL-----";
		$end = "-----END";
		return parent::pem2der($elem, $start, $end);
	}

} /* end CRL */