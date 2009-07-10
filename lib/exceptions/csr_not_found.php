<?php
  /* key_not_found.php
   *
   * KeyNotFoundException  thrown when the CSR is not found in the database.
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
class CSRNotFoundException extends Exception
{
     private $msg;
     public function __construct($messag, $code = 0)
     {
		  $this->msg = $message;
		  parent::__construct($message, $code);
     }
     public function __toString() 
     {
          return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
     }
}
