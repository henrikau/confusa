<?php
declare(encoding = 'utf-8');
require_once 'confusa_gen.php';

/**
 * KeyRevokeExceptin exception thrown when revoking the cert failed.
 *
 * When the certificate-manager cannot revoke the certificate, for whatever
 * reason, this exception shall be thrown. It is up to the caller to handle
 * this. The message embedded in the exception shall explain the reason, and
 * thus, cert-manager should not deal with the framework *at-all*
 *
 * PHP Version 5
 * @author Henrik Austad <henrik.austad@uninett.no>
 */

class CGE_KeyRevokeException extends ConfusaGenException
{
    public function __construct($message, $code=0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} /* end class CGE_KeyRevokeException */
?>
