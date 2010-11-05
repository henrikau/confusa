<?php
declare(encoding = 'utf-8');
require_once 'confusa_gen.php';

/**
 * CryptoElementException
 *
 * @author Henrik Austad <henrik@austad.us>
 * @package ca
 */
class CryptoElementException extends ConfusaGenException
{
	public function __construct($message = null, $code = 0)
	{
		parent::__construct($message);
	}

	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
} /* end class ConfusaGenException */
?>
