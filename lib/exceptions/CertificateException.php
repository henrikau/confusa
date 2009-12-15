<?php
  /** CertificateException
   *
   *
   * Execption thrown by Config when the certificate has expired
   *
   * @author Henrik Austad <henrik.austad@uninett.no>
   */
class CertificateException extends Exception
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
