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
 */

/* copied from simplesamlphp */
function ssp_readline($prompt = '') {
    echo $prompt;
    return rtrim( fgets( STDIN ), "\n" );
}

require_once 'oauth_config.php';

$REQ_TOKEN_URL = $oauthc_config['portal_base_address'] . '/api/oauth.php/request';
$AUTHORIZE_URL = $oauthc_config['portal_base_address'] . '/api/oauth.php/authorize';
$ACC_TOKEN_URL = $oauthc_config['portal_base_address'] . '/api/oauth.php/access';
$CONTENT_URL = $oauthc_config['portal_base_address'] . '/index.php?oauth=yes';

$oauth = new OAuth($oauthc_config['oauth_consumer_key'],
                   $oauthc_config['oauth_consumer_secret']);

/* don't use this in a production environment. PHP-curl can be a bit nasty
 * about where it is looking for root certificates, especially if you can
 * not control it directly. */
$oauth->disableSSLChecks();

try {
	$reqToken = $oauth->getRequestToken($REQ_TOKEN_URL);
} catch (OAuthException $oae) {
	echo "The following exception occured when trying to get a request token: " . $oae->getMessage() . "\n";
}

print_r($reqToken);

echo "Now you have to authorize the following token: " . $AUTHORIZE_URL . "?oauth_token=" . $reqToken['oauth_token'] . "\n";
ssp_readline("Press any key to continue...\n");

$oauth->setToken($reqToken['oauth_token'], $reqToken['oauth_token_secret']);
$accessToken = $oauth->getAccessToken($ACC_TOKEN_URL);

$oauth->setToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);

$params = array();
$params['start_login'] = 'yes';
if ($oauth->fetch($CONTENT_URL, $params)) {
	echo "Fetched content-url " . $CONTENT_URL . "\n";
	echo $oauth->getLastResponse() . "\n";
}
?>
