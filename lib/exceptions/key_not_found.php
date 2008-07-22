<?php
class KeyNotFoundException extends Exception
{
     private $msg;

     public function __construct($message, $code = 0)
          {
               $this->msg = $message;
               parent::__construct($message, $code);
          }

     public function __toString() 
          {
          return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
          }

}
