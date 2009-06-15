<?php
declare(encoding = 'utf-8');
require_once('confusa_gen.php');

/**
 * KeySignException
 * Exception thrown when signing a CSR using Confusa fails unexpectedly.
 * Stub that can be extended in the future.
 *
 * PHP version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class KeySignException extends ConfusaGenException
{

    public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} /* end class KeySignException */
?>
