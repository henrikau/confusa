<?php

ini_set('mbstring.http_input', 'pass');
ini_set('mbstring.http_output', 'pass');


/*
 * Wrap Curl calls in a convenience class
 */
class CurlWrapper
{
	/**
	 * Send a POST message containing $postData to the endpoint in $url
	 *
	 * @param $url string the endpoint to which the POST message should be sent
	 * @param $method string whether GET or POST should be used to conact the
	 *				remote site
	 * @param $postData array the POST variables that are to be send
	 *
	 * @return string the result of the communication
	 */
	public static function curlContact($url, $method="get", $postData=null)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		if ($method == "post") {
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		}

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
		$status = curl_errno($ch);
        curl_close($ch);

		if ($status != 0) {
			throw new ConfusaGenException("Could not connect properly to remote " .
			                              "endpoint $url! Maybe the Confusa instance is misconfigured? " .
			                              "Please contact an administrator!");
		}

		return $data;
	}

	/* curlContactCert() Use X.509 authN to contact target
	 *
	 * This function will not use username/password to authN to endpoint but
	 * rather use a X.509 cert/key-pair to authN.
	 *
	 * curl will only support POST for this.
	 *
	 * @param String url the url to the endpoint
	 * @param String $key the key to use
	 * @param String $cert the certificate belonging to the key
	 * @param String $keypw passphrase for the private key
	 * @param Array $postdata the data to send.
	 * @access public
	 * @return Styring|false The response from the endpoint
	 * @static
	 */
	public static function curlContactCert($url, $key, $cert, $keypw = false, $postData = null)
	{
		if (is_null($key) || is_null($cert) || $key === "" || $cert === "")
		{
			throw new ConfusaGenException("Empty key or certificate received ".
										  "when using curlContactCert(). ".
										  "Aborting curl-transfer to url: $url");
		}

		if (is_null($postData) || !is_array($postData) || count($postData) == 0) {
			return false;
		}

		/* Do basic URL filtering */
		$curlurl = Input::sanitizeURL($url);
		if (is_null($curlurl) || $curlurl === "" || filter_var($curlurl, FILTER_VALIDATE_URL)) {
			return false;
		}

		/* key should be encrypted, if not, do not use it (not safe!) */
		$start = "-----BEGIN ENCRYPTED PRIVATE KEY-----";
		if (substr($key, 0, strlen($start)) !== $start) {
			Logger::log_event(LOG_NOTICE, "Trying to use curlContactCert with unecrypted private key, aborting.");
			return false;
		}
		$rkey = openssl_pkey_get_private($key, $keypw);
		if ($rkey === false) {
			return false;
		}
		if (!openssl_x509_check_private_key($cert, $rkey)) {
			/* echo "cert/key mismatch\n"; */
			/* echo "key:\n" . $key; */
			/* echo "\ncert:\n" . $cert; */
			return false;
		}
		$rcert = openssl_x509_read($cert);
		$cert_data = openssl_x509_parse($rcert);
		echo "validTo: " . $cert_data['validTo'] . "\n";
		echo "time():  " . time() . "\n";
		if ($cert_data['validTo'] < time()) {
			echo "Certificate has expired!\n";
			/* expired cert */
			return false;
		}
		$options = 	array(
			CURLOPT_URL => $curlurl,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  2,
			CURLOPT_SSLKEY => 'my.key',
			CURLOPT_SSLCERT => 'my.crt',
			CURLOPT_SSLKEYPASSWD => 'foobar'
		);

		$ch = curl_init();
		return "data";			/* to trigger tests, this is bogus data */
	}
}
?>
