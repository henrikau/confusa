<?php

require_once 'confusa_gen.php';

/**
 * AuthException
 *
 * thrown if something goes wrong in user authentication
 */
class AuthException extends ConfusaGenException
{
	public function __construct($message = NULL, $code = 0)
	{
		parent::__construct($message);
	}

	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
} /* end class AuthException */
?>
