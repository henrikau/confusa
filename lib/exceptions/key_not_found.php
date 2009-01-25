<?php
  /* key_not_found.php
   * KeyNotFoundException
   *
   * Execption thrown by Config when the supplied key is not found in the
   * config-table.
   *
   * Author: Henrik Austad <henrik.austad@uninett.no>
   */
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
