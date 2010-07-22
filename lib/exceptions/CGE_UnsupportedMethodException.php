<?php
class CGE_UnsupportedMethodException extends ConfusaGenException
{
 public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} /* end CGE_UnsupportedMethodException */
?>