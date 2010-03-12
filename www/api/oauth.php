<?php

/**
 * Authorize an OAuth request token using SAML authentication method.
 * This more or less replicates simplesamlphp functionality, but we need
 * information about the user's IdP in order to be able to guess the NREN.
 *
 */
	require_once '../confusa_include.php';
	require_once 'Config.php';
	$sspdir = Config::get_config('simplesaml_path');
	require_once $sspdir . '/lib/_autoload.php';

	$path = $_SERVER['PATH_INFO'];
	$requestToken = $_REQUEST['oauth_token'];

	switch($path) {
	case '/authorize':
		$store = new sspmod_oauth_OAuthStore();
		$server = new sspmod_oauth_OAuthServer($store);

		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

		$server->add_signature_method($hmac_method);
		$server->add_signature_method($plaintext_method);

		$session = SimpleSAML_Session::getInstance();

		if (!$session->isValid('default-sp')) {
			SimpleSAML_Auth_Default::initLogin('default-sp',
			                                   SimpleSAML_Utilities::selfURL());
		}

		$attributes = $session->getAttributes();
		$idp        = $session->getIdP();
		$attributes['idp'] = array($idp);
		$store->authorize($requestToken, $attributes);

		echo "Your request is now authorized.<br />\n";
		break;
	}
?>
