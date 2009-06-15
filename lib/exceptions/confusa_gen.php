<?php
declare(encoding = 'utf-8');

/**
 * ConfusaGenException
 * Base class for all exceptions that are thrown during runtime from Confusa.
 * Stub that can be extended in the future.
 *
 * PHP version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class ConfusaGenException extends Exception
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
