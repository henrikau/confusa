<?php
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
			throw new RemoteAPIException("Could not connect properly to remote API " .
										"endpoint $url! Maybe the Confusa instance is misconfigured? " .
										"Please contact an administrator!");
		}

		return $data;
	}
}
?>
