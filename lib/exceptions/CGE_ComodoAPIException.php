<?php
declare(encoding = 'utf-8');
require_once 'confusa_gen.php';

/**
 * ComodoAPIException thrown if a request at the remote certificate-API does not
 * return the expected result.
 * Exception stub that can be extended in the future.
 *
 * PHP version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CGE_ComodoAPIException extends ConfusaGenException
{

    public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} /* end class CertificateQueryException */
?>
