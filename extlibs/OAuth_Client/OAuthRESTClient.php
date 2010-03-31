#/usr/bin/env php
<?php

/**
 * Simple PHP script to test Confusa's OAuth-authmanager.
 *
 * Requires PHP::OAuth, see http://www.php.net/manual/en/class.oauth.php
 *
 * in short: sudo pecl install oauth-0.99-9
 * you'll need some dependencies like
 *        * php5-dev
 *        * libcurl-openssl-dev
 *
 *
 * Class to connect to the Confusa REST-API and perform the basic API operations.
 * Includes functionality for the auth-model (getting OAuth-tokens etc.).
 *
 */
class OAuthRESTClient
{
	/* the API usage authorization class */
	private $oauth;
	private $reqTokenURL;
	private $authorizeURL;
	private $accTokenURL;
	private $serviceBaseURL;

	function __construct()
	{
		require_once 'oauth_config.php';
		$this->oauth = new OAuth($oauthc_config['oauth_consumer_key'],
						   $oauthc_config['oauth_consumer_secret']);
		/* don't use this in a production environment. PHP-curl can be a bit nasty
		 * about where it is looking for root certificates, especially if you can
		 * not control it directly. */
		$this->oauth->disableSSLChecks();

		$this->reqTokenURL = $oauthc_config['portal_base_address'] . '/api/oauth.php/request';
		$this->authorizeURL = $oauthc_config['portal_base_address'] . '/api/oauth.php/authorize';
		$this->accTokenURL = $oauthc_config['portal_base_address'] . '/api/oauth.php/access';
		$this->serviceBaseURL = $oauthc_config['portal_base_address'];
	}

	/* copied from simplesamlphp */
	function ssp_readline($prompt = '') {
		echo $prompt;
		return rtrim( fgets( STDIN ), "\n" );
	} /* end ssp_readline */

	public function getAuthorization()
	{
		/* try to get cached access token first */
		if (file_exists(".acc_tok_cache")) {

			while ($reuseAccTok != 'y' && $reuseAccTok != 'n') {
				$reuseAccTok = readline("Reuse cached access token (y/n)? ");
				readline_add_history($reuseAccTok);
			}

			if ($reuseAccTok == 'y') {
				$accTokenString = file_get_contents(".acc_tok_cache");
				$accessToken = unserialize($accTokenString);

				echo "Using access token: ";
				print_r($accessToken);
			}
		}

		/* no cached access token, get a new one */
		if (empty($accessToken)) {
			try {
				$reqToken = $this->oauth->getRequestToken($this->reqTokenURL);
			} catch (OAuthException $oae) {
				echo "The following exception occured when trying to get a request token: " .
				     $oae->getMessage() . "\n";
			}

			print_r($reqToken);

			echo "Now you have to authorize the following token: " .
			     $this->authorizeURL . "?oauth_token=" . $reqToken['oauth_token'] . "\n";
			$this->ssp_readline("Press any key to continue...\n");
			$accessToken = $reqToken;
			$this->oauth->setToken($reqToken['oauth_token'], $reqToken['oauth_token_secret']);
			$accessToken = $this->oauth->getAccessToken($this->accTokenURL);
			$accessTokenString = serialize($accessToken);
			file_put_contents(".acc_tok_cache", $accessTokenString);
		}

		$this->oauth->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
	} /* end getAuthorization */

	/**
	 * List the certificates of the authN user (will return XML)
	 */
	public function listCertificates()
	{
		$endpoint = $this->serviceBaseURL . '/api/certificates.php';
		if ($this->oauth->fetch($endpoint)) {
			echo "Fetched content-url " . $endpoint . "\n";
			echo $this->oauth->getLastResponse() . "\n";
		}
	} /* end function listCertificates */

	/**
	 * Download a single certificate identified by the certID parameter
	 *
	 * @param $certID the unique identifier of the certificate that should be
	 *                downloaded
	 */
	public function downloadCertificate($certID)
	{
		$endpoint = $this->serviceBaseURL . "/api/certificates.php/$certID";
		if ($this->oauth->fetch($endpoint)) {
			echo "Downloaded cert from API endpoint " . $endpoint . "\n";
			echo $this->oauth->getLastResponse() . "\n";
		}
	}

	/**
	 * Upload a CSR to the portal in order to get it signed
	 *
	 * @param $csrFile string Path to the file containg the csr
	 */
	public function uploadCertRequest($csrFile)
	{
		$endpoint =  $this->serviceBaseURL . "/api/certificates.php";
		$csr = file_get_contents($csrFile);

		$params = array();
		$params['csr'] = $csr;
		if ($this->oauth->fetch($endpoint, $params, OAUTH_HTTP_METHOD_POST)) {
			echo "Posted CSR to API-endpoint " . $endpoint . "\n";
			echo $this->oauth->getLastResponse() . "\n";
		}
	} /* end uploadCertRequest */
} /* end class OAuth-REST-client */

$shortopts = "ld:u:";
$options = getopt($shortopts);

$apiConnection = new OAuthRESTClient();
$apiConnection->getAuthorization();

if (isset($options['l'])) {
	$apiConnection->listCertificates();
} else if (isset($options['d'])) {
	$certID = $options['d'];
	$apiConnection->downloadCertificate($certID);
} else if (isset($options['u'])) {
	$csrFile = $options['u'];
	$apiConnection->uploadCertRequest($csrFile);
} else {
	echo "Supplied the wrong arguments!\nCall \"php OAuthRESTClient.php -<opt>\" with <opt>:\n";
	echo "\t-l\t\tList all certificates of the user\n";
	echo "\t-d <cert-id>\tDownload certificate with <cert-id>\n";
	echo "\t-u <csr-file>\tUpload CSR file located at <csr-file>\n";
	exit(1);
}


?>
