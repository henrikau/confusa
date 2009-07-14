<?php
/* Authorize the user with OAuth instead of a session.
 * To be able to authorize herself with OAuth, the user must have done the following:
 *
 * 1.) obtained a request token from the service provider (e.g. simplesamlphp SP)
 * 2.) authorized that request token at her identity provider
 * 3.) obtained an access token from the service provider
 *
 * An access token is always tied to a timestamp and as with a session, its validity is limited.
 * Currently the validity is hardcoded to 5 minutes in the simplesamlphp OAuth module.
 * The access request contains protection against tampering - nonces and a signature over the
 * url and the request parameters.
 *
 * See the excellent documentation at:
 * https://rnd.feide.no/content/federated-command-line-client-authentication-simplesamlphp-and-oauth
 */
class ConfusaOAuth {
	private $oauth_store = NULL;
	private $oauth_server = NULL;
	private $consumer = NULL;
	private $token = NULL;

	private static $instance = NULL;

	public function getInstance() {

		if (!isset(self::$instance)) {
			self::$instance = new ConfusaOAuth();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->oauth_store = new sspmod_oauth_OAuthStore();
		$this->oauth_server = new sspmod_oauth_OAuthServer($this->oauth_store);
		/* currently support HMAC_SHA1 signatures exclusively
		 */
		$this->oauth_server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
		$request = OAuthRequest::from_request();
		/* do verification *once* here, because we may not reuse the request nonce at any point
		 */
		list($this->consumer, $this->token) = $this->oauth_server->verify_request($request);
	}

	/* Get the attributes about the user from the OAuth store
	 * To be able to retrieve the attributes, the request must contain an authorized access token
	 */
	public function getAttributes() {
		$data = $this->oauth_store->getAuthorizedData($this->token->key);
		return $data;
	}

	/* Returns if the token is authorized. The access token will pass if certain criteria are met,
	 * e.g. if the nonce parameter is not reused, the signature over all parameters and the url is correct and the
	 * access token is authorized
	 */
	public function isAuthorized() {
		return isset($this->token);
	}
}
?>
