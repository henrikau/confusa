<?php
declare(encoding = 'utf-8');

/**
 * FileException
 * Exception that indicates an error in Confusa that occured while processing
 * files, e.g. during up- and downloading.
 * Stub that can be extended in the future.
 *
 * PHP version 5
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class FileException extends ConfusaGenException
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
