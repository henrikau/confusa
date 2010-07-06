<?php
require_once 'CryptoElementException.php';
abstract class CryptoElement
{
	protected static $KEY_ENCODING_PEM = 1;
	protected static $KEY_ENCODING_DER = 2;

	protected $content;
	protected $encoding;

	function __construct($content)
	{
		if (is_null($content)) {
			throw new CryptoElementException(__CLASS__ . " empty content. A CryptoElement with nothing as element is moot.");
		}
		$this->content = $content;
	}

	/**
	 * __toString() return string-representation of object
	 *
	 * Function will return the element (if in PEM-format).
	 *
	 * @param	void
	 * @return	String object as string
	 * @access	public
	 */
	function __toString()
	{
		if (!is_null($this->content)) {
			return $this->getPEMContent();
		}
		return "CryptoElement";
	}

	/**
	 * __destruct() cleanup when object is lost.
	 *
	 * @param	void
	 * @return	void
	 * @access	public
	 */
	function __destruct()
	{
		unset($this->content);
		unset($this->encoding);
	}

	/**
	 * getSubject() return the complete subject of the Element
	 *
	 * @param	void
	 * @return	String|false the subject, false if error
	 * @access	public
	 */
	public abstract function getSubject();

	/**
	 * getLength() return the number of bits in the Element
	 *
	 * @param	void
	 * @return	Integer|false the number of bits, false if error
	 * @access	public
	 */
	public abstract function getLength();

	/**
	 * getType() return the type of the key
	 *
	 * The type is in most cases RSA, thus 'rsa' will be returned.
	 * Currently supported types:
	 *	- rsa
	 *	- dsa
	 *	- dh
	 *	- ec
	 * If none of the supported types are found, false is returned.
	 *
	 * @param	void
	 * @return	String|false the type of key in lowercase, false if error
	 * @access	public
	 */
	public abstract function getType();

	/**
	 * getPubKeyHash() return the hash of the public-key
	 *
	 * @param	void
	 * @return	String|false the sha1sum of the public-key
	 * @access	public
	 */
	public abstract function getPubKeyHash();

	/**
	 * isValid() Test to see if the element is valid.
	 *
	 * @param	void
	 * @return	Boolean true if element is valid (no errors found)
	 * @access	public
	 */
	public abstract function isValid();

	/**
	 * getPEMContent() return the Element in PEM-encoded form
	 *
	 * @param	raw if the element should be returned in it's raw form
	 * @return	String the PEM-encoded certificate
	 * @access	public
	 */
	public abstract function getPEMContent($raw = true);

	/**
	 * getDERContent() return the Element in DER-encoded form
	 *
	 * @param	void
	 * @return	String the DER-encoded certificate
	 * @access	public
	 */
	public abstract function getDERContent($raw = true);

	/**
	 * Convert a CSR in PEM-format to DER format
	 *
	 * @author	Henrik Austad <henrik@austad.us>
	 */
	protected function pem2der($pem, $start, $end)
	{
		if ($this->getEncoding($pem) !== self::$KEY_ENCODING_PEM) {
			return false;
		}
		$pem = substr($pem, strpos($pem, $start)+strlen($start));
		$pem = substr($pem, 0, strpos($pem, $end));
		$der = base64_decode($pem);
		return $der;
	}

	/**
	 * @see pem2der
	 */
	protected function der2pem($der_data, $start, $end)
	{
		if ($this->getEncoding($der_data) !== self::$KEY_ENCODING_DER) {
			return false;
		}
		$pem = chunk_split(base64_encode($der_data), 64, "\n");
		return "$start$pem$end";
	}

	/**
	 * getEncoding()
	 */
	protected function getEncoding($element, $start, $end)
	{
		$start_pos = strpos($element, $start);
		$end_pos   = strpos($element, $end);
		if ($start_pos && $end_pos) {
			return self::$KEY_ENCODING_PEM;
		}
		return self::$KEY_ENCODING_DER;
	} /* end getEncoding() */
} /* end CryptoElement */

?>
