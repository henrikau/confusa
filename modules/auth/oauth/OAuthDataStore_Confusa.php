<?php
$sspdir = Config::get_config('simplesaml_path');
require_once $sspdir . '/modules/oauth/libextinc/OAuth.php';
require_once $sspdir . '/lib/_autoload.php';
require_once 'confusa_constants.php';
require_once 'logger.php';

/**
 * Implement Confusa's own OAuth-datastore. This is mostly done to couple the
 * validity of the OAuth-access token to the (per NREN configurable)
 * desired reauth-timeout.
 *
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 * @since v0.6-rc0
 */
class OAuthDataStore_Confusa extends OAuthDataStore
{
	private $store;

	function __construct()
	{
		$this->store = new sspmod_core_Storage_SQLPermanentStorage('oauth');
	}

	/* those functions are mostly copied from simplesamlphp */
	public function authorize($requestToken, $data)
	{
		Logger::log_event(LOG_INFO, "[OAuth_DataStore] authorizing request token $requestToken");
		$this->store->set('authorized', $requestToken, '', $data, 60*30);
	}

	public function isAuthorized($requestToken)
	{
		return $this->store->exists('authorized', $requestToken, '');
	}

	public function getAuthorizedData($token)
	{
		$data = $this->store->get('authorized', $token, '');
		return $data['value'];
	}

	public function moveAuthorizedData($requestToken, $accessToken) {
		$this->authorize($accessToken, $this->getAuthorizedData($requestToken));
		$this->store->remove('authorized', $requestToken, '');
	}

	function lookup_consumer($consumer_key)
	{
		if (! $this->store->exists('consumers', $consumer_key, '')) {
			return NULL;
		}

		$consumer = $this->store->get('consumers', $consumer_key, '');
		return new OAuthConsumer($consumer['value']['key'], $consumer['value']['secret'], NULL);
	}

	function lookup_token($consumer, $token_type, $token)
	{
		$this->store->removeExpired();
		$data = $this->store->get($token_type, $token, $consumer->key);
		if ($data == NULL) {
			throw new Exception('Could not find token');
		}

		return $data['value'];
	}

	function lookup_nonce($consumer, $token, $nonce, $timestamp) {
		if ($this->store->exists('nonce', $nonce, $consumer->key)) {
			return TRUE;
		}

		$this->store->set('nonce', $nonce, $consumer->key, TRUE, 60*60*24*14);
		return FALSE;
	}

	function new_request_token($consumer)
	{
		$token = new OAuthToken(SimpleSAML_Utilities::generateID(), SimpleSAML_Utilities::generateID());
		$this->store->set('request', $token->key, $consumer->key, $token, 60*30);
        return $token;
	}

	/* change the functionality of the simplesamlphp access token request
	 * mechanisms to be able to use our own timeout
	 *
	 * @param $requestToken string The authorized requestToken that gets an access token
	 * @param $consumer string The consumer that accesses the functionality
	 *
	 */
	function new_access_token($requestToken, $consumer)
	{
		$data = $this->getAuthorizedData($requestToken->key);

		if (isset($data[ConfusaConstants::$OAUTH_VALIDITY_ATTRIBUTE])) {
			$validity = $data[ConfusaConstants::$OAUTH_VALIDITY_ATTRIBUTE];
		} else {
			$validity = ConfusaConstants::$DEFAULT_REAUTH_TIMEOUT;
		}

		/* need the validity in seconds */
		$validity = $validity*60;

		Logger::log_event(LOG_DEBUG, '[OAuthDataStore_Confusa] OAuth new_access_token(' . $requestToken . ',' . $consumer . ')');
		$token = new OAuthToken(SimpleSAML_Utilities::generateID(), SimpleSAML_Utilities::generateID());
		$this->store->set('access', $token->key, $consumer->key, $token, $validity);
		return $token;
    }
}
?>