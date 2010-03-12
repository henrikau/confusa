<?php

abstract class CryptoElement
{
	protected static $KEY_ENCODING_PEM = 1;
	protected static $KEY_ENCODING_DER = 1;

	protected $content;
	protected $encoding;

	function __construct($content)
	{
		if (is_null($content)) {
			throw new CryptoElementException(__CLASS__ . " need content to continue.");
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
		if (!is_null($this->content) &&
		    $this->encoding == CryptoElement::$KEY_ENCODING_PEM) {
			return $this->content;
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

} /* end CryptoElement */

?>
