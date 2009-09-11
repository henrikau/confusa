<?php
require_once 'confusa_gen.php';

class CriticalAttributeException extends ConfusaGenException
{
    public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ":[" . $this->code . "]: " . $this->message . "\n";
    }
}
?>
