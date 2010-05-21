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
	require_once 'Translator.php';

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
		$store = new OAuthDataStore_Confusa();
		$server = new sspmod_oauth_OAuthServer($store);

		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

		$server->add_signature_method($hmac_method);
		$server->add_signature_method($plaintext_method);

		$req = OAuthRequest::from_request();
		$token = $server->fetch_request_token($req);
		echo $token;
		break;

	case '/authorize':
		$requestToken = $_REQUEST['oauth_token'];
		$person = new Person();

		$auth_idp = new Confusa_Auth_IdP($person);
		$auth_idp->authenticate(TRUE);
		$auth_idp->reAuthenticate();

		if (!$person->isAuth()) {
			header("HTTP/1.1 403 Forbidden");
			echo "User is not authenticated!";
			exit;
		}

		$attributes = $auth_idp->getAttributes();
		$idp = $attributes['idp'][0];

		$accTokenValidity = getAccessTokenTimeout($idp);

		$consent_val = mt_rand();
		$_SESSION['oauth_authZ'] = TRUE;
		$_SESSION['request_token'] = $requestToken;
		$_SESSION['consent_val'] = $consent_val;

		$store = new OAuthDataStore_Confusa();
		$consumer_key = $store->consumer_from_token($requestToken);
		$consumer_info = $store->get_consumer_info($consumer_key);

		$tpl = new Smarty();
		$tpl->template_dir= Config::get_config('install_path').'templates';
		$tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;
		$tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;
		$subscriber = $person->getSubscriber();

		if (isset($subscriber)) {
			$help_email = $subscriber->getHelpEmail();
			$tpl->assign('help_email', $help_email);
		}

		$tpl->assign('consent_val', $consent_val);
		$tpl->assign('consumer_key', $consumer_key);
		$tpl->assign('consumer_name', $consumer_info['name']);
		$tpl->assign('consumer_description', $consumer_info['description']);
		$tpl->assign('access_duration', $accTokenValidity);
		$translator = new Translator();
		$translator->guessBestLanguage($person);
		$translator->decorateTemplate($tpl, 'oauth');
		$tpl->display('api/oauth_consent.tpl');
		break;

	case '/consent':
		$person = new Person();
		$auth_idp = new Confusa_Auth_IdP($person);
		$auth_idp->authenticate(FALSE);

		if (!$person->isAuth()) {
			header("HTTP/1.1 412 Precondition Failed");
			echo "May not call the consent endpoint before the user " .
			     "authenticated with their IdP!";
			exit;
		}

		if ($_SESSION['oauth_authZ'] !== TRUE) {
			header("HTTP/1.1 412 Precondition Failed");
			echo "May not call the consent endpoint before the user " .
			     "passed the authorization endpoint!";
			exit;
		}

		if (empty($_POST['consent_val']) ||
		    $_POST['consent_val'] != $_SESSION['consent_val']) {
			header("HTTP/1.1 403 Forbidden");
			echo "The received consent token does not match the generated " .
			     "one! Please follow authorize/consent steps in proper order.";
			exit;
		}

		$requestToken = $_SESSION['request_token'];

		/* don't keep sensitive session information, avoid malicious reuse */
		$_SESSION['oauth_authZ'] = FALSE;
		$_SESSION['request_token'] = NULL;
		$_SESSION['consent_val'] = NULL;

		$attributes = $auth_idp->getAttributes();
		$idp = $attributes['idp'][0];

		$accTokenValidity = getAccessTokenTimeout($idp);

		$attributes[ConfusaConstants::$OAUTH_VALIDITY_ATTRIBUTE] = $accTokenValidity;

		$store = new OAuthDataStore_Confusa();
		$server = new sspmod_oauth_OAuthServer($store);

		$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
		$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();

		$server->add_signature_method($hmac_method);
		$server->add_signature_method($plaintext_method);
		$store->authorize($requestToken, $attributes);

		if (isset($_GET['relayURL'])) {
			$relayURL = $_GET['relayURL'];
			header("Location: $relayURL");
		} else {
			echo "Your request is now authorized.<br />\n";
		}
		break;

	case '/noconsent':
		header("HTTP/1.1 403 Forbidden");
		echo "User did not give consent to sharing information with the " .
		     "OAuth service provider!";
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
