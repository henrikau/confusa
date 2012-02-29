<?php
class CSR_PKCS10
{
	private $content;
	function __construct($c)
	{
		$this->content = $c;
	}
	function getType()
	{
		return "rsa";
	}
	function getPubKeyHash()
	{
		return sha1("rsa");
	}
	function getSubject()
	{
		return "mock";
	}

}
?>

