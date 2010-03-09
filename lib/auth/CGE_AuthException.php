<?php

require_once 'confusa_gen.php';

/**
 * CGE_AuthException
 *
 * thrown if something goes wrong in user authentication
 * @package auth
 */
class CGE_AuthException extends ConfusaGenException
{
	public function __construct($message = NULL, $code = 0)
	{
		parent::__construct($message);
	}

	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
} /* end class CGE_AuthException */
?>
