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
	require_once 'confusa_constants.php';
	require_once 'OAuthDataStore_Confusa.php';
	require_once 'MDB2Wrapper.php';
	require_once 'NREN.php';
	require_once 'NREN_Handler.php';
	require_once 'Person.php';
	require_once 'Confusa_Auth_IdP.php';

	$path = $_SERVER['PATH_INFO'];

	function getAccessTokenTimeout($idp_url)
	{
		$query = "SELECT reauth_timeout FROM nrens n, idp_map m " .
		         "WHERE m.nren_id = n.nren_id AND m.idp_url = ?";

		try {
			$res = MDB2Wrapper::execute($query, array('text'), array($idp_url));
		} catch (ConfusaGenException $cge) {
			throw new CGE_AuthException("No NREN connected to IdP $idp_url!");
		}

		if (count($res) == 1) {
			return $res[0]['reauth_timeout'];
		} else {
			return ConfusaConstants::$DEFAULT_REAUTH_TIMEOUT;
		}
	}

	switch($path) {
	case '/request':
		require_once $sspdir . ConfusaConstants::$OAUTH_REQUEST_ENDPOINT;
		break;
	case '/authorize':
		$requestToken = $_REQUEST['oauth_token'];

		$store = new OAuthDataStore_Confusa();
		$server = new sspmod_oauth_OAuthServer($store);

		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

		$server->add_signature_method($hmac_method);
		$server->add_signature_method($plaintext_method);
		$person = new Person();

		$auth_idp = new Confusa_Auth_IdP($person);
		$auth_idp->authenticate(TRUE);
		$auth_idp->reAuthenticate();

		$attributes = $auth_idp->getAttributes();
		$accTokenValidity = getAccessTokenTimeout($idp);
		$attributes[ConfusaConstants::$OAUTH_VALIDITY_ATTRIBUTE] = $accTokenValidity;
		$store->authorize($requestToken, $attributes);

		if (isset($_GET['relayURL'])) {
			$relayURL = $_GET['relayURL'];
			header("Location: $relayURL");
		} else {
			echo "Your request is now authorized.<br />\n";
		}

		break;
	case '/access':
		$store = new OAuthDataStore_Confusa();
		$server = new sspmod_oauth_OAuthServer($store);

		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

		$server->add_signature_method($hmac_method);
		$server->add_signature_method($plaintext_method);

		$req = OAuthRequest::from_request();
		$requestToken = $req->get_parameter('oauth_token');

		if (!$store->isAuthorized($requestToken)) {
			throw new Exception('Your request was not authorized. Request token [' . $requestToken . '] not found.');
		}

		$accessToken = $server->fetch_access_token($req);
		$data = $store->moveAuthorizedData($requestToken, $accessToken->key);

		echo $accessToken;
		break;
	default:
		header("HTTP/1.1 400 Bad Request");
		exit;
	}
?>
