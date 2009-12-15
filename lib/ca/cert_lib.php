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

	if (!isset($x509cert) || $x509cert == "") {
		return null;
	}
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


/**
 * openssl_x509_keylength return the length of the X.509 certificate.
 *
 * This function makes a small and convenient wrapper for finding the lenght of
 * the key in a certificate. It has been given the same naming as the in-library
 * X.509 functions provided by PHP.
 *
 * @param String $x509cert the certificate in textual form
 * @return integer the length of the key or negative upon error
 */
function openssl_x509_keylength($x509cert)
{
	$details = openssl_x509_key_details($x509cert);
	if (is_null($details)) {
		return -1;
	}
	return $details['bits'];
}

function openssl_x509_key_details($x509cert)
{
	if (is_null($x509cert)) {
		return null;
	}

	$cert = openssl_x509_read($x509cert);
	if (is_null($cert)) {
		return null;
	}

	$key = openssl_get_publickey($cert);
	if (is_null($key)) {
		return null;
	}
	$details = openssl_pkey_get_details($key);

	if (is_null($details) || ! is_array($details)) {
		return null;
	}
	return $details;

}
?>