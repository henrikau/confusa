<?php
require_once 'confusa_gen.php';

/**
 * RemoteCredentialException exception thrown when the credentials supplied for
 * the Comodo API seem to be invalid or not present at all
 *
 * To contact the Comodo-API some per-NREN credentials are required - a
 * loginName, a loginPassword and an alliance partner name. If this data is
 * missing some functionality of the Comodo API will not be available.
 *
 * Throw that exception to indicate this condition.
 *
 * PHP Version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */

class CGE_ComodoCredentialException extends ConfusaGenException
{
    public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} /* end class CGE_ComodoCredentialException */
?>
