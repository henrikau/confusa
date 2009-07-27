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

    /*
     * Get the exception message in HTML format
     * It would be nicer to override getMessage() for that, but that
     * is final and seemingly PHP does not support parameter overloading
     */
    public function getHTMLMessage()
    {
      return "<p><font class=\"exception\">" .
             parent::getMessage() .
             "</font></p>";
    }
} /* end class ConfusaGenException */
?>
