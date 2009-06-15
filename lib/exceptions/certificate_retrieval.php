<?php
declare(encoding = 'utf-8');
require_once('confusa_gen.php');

/**
 * CertificateRetrievalException thrown if the attempt to retrieve a certificate
 * that is supposed to exist does not lead to the expected result.
 * Stub that can be extended in the future.
 *
 * PHP version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CertificateRetrievalException extends ConfusaGenException
{

    public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} /* end class CertificateRetrievalException */
?>
