<?php
declare(encoding = 'utf-8');

class CryptoElementException extends Exception
{

    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
?>
