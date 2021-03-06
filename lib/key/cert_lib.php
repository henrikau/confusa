<?php

  /** cert_fingerprint
   *
   * Return the fingerprint of the privided certificate or an empty string if
   * the certificate is malformed.
   *
   * This function is intentionally named as the way the other PHP implemented
   * openssl functions are. It does, however, escape to shell to find the
   * fingerprint.
   *
   * @x509cert : the certificate from which to extract the fingerprint.
   * @return   : a string containing the fingerprint
 */
function openssl_x509_fingerprint($x509cert, $hashOnly = true)
{
	if (!isset($x509cert) || $x509cert === "")
		return "";
	$cmd = "echo \"" . $x509cert . "\" | openssl x509 -fingerprint -noout";
	$fprint = shell_exec($cmd);
	if ($hashOnly) {
		$fprint = substr($fprint, strpos($fprint, '=') + 1);
	}
	return $fprint;
}

function openssl_x509_serial($x509cert)
{

	$cmd ="echo \"" . $x509cert . "\" | openssl x509  -serial -noout|cut -d '=' -f2";
	$sprint = shell_exec($cmd);
	return $sprint;
}
function openssl_crl_export($crl)
{
	if (!isset($crl)) {
		return;
	}
	$cmd ="echo \"" . $crl . "\" | openssl crl -text -noout";
	return shell_exec($cmd);
}
?>