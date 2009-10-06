<?php
/**
 * This class contains constants that are needed in different places of
 * Confusa. Constants differ from config flags in that we don't expect the
 * user to change them, except if the user is very technically savy and/or
 * has special requirements in which case the Constants file can be changed
 * as well. This also helps to keep the config-file clear and smaller.
 */
class ConfusaConstants {
	/* The following fields are used when the Comodo-API is called
	 * for certificate creation
	 *
	 * Probably you don't want to change them, since that will most likely break
	 * online certificate signing. The Comodo API documentation can be found at
	 *
	 * http://secure.comodo.net/api/pdf/reseller/customclientcertificates/
	 *
	 */
	public static $CAPI_APPLY_ENDPOINT = 'https://secure.comodo.com/products/!applyCustomClientCert';
	public static $CAPI_AUTH_ENDPOINT = 'https://secure.comodo.net/products/!AutoAuthorize';
	public static $CAPI_COLLECT_ENDPOINT = 'https://secure.comodo.net/products/download/CollectCCC';
	public static $CAPI_LISTING_ENDPOINT = 'https://secure.comodo.net/products/!Tier2ResellerReport';
	public static $CAPI_REVOKE_ENDPOINT = 'https://secure.comodo.net/products/!AutoRevokeCCC';
	/* these fields are for informational use, when the user clicks "view CRL/view root cert"
	 * in Confusa */
	public static $CAPI_ROOT_CERT = 'http://crt.tcs.terena.org/TERENAeSciencePersonalCA.crt';
	public static $CAPI_CRL = 'http://crt.tcs.terena.org/TERENAeSciencePersonalCA.crt';
	/* used in the API */
	public static $CAPI_ESCIENCE_ID = '285';
	/* if we ever want to issue e-mail certificates */
	public static $CAPI_PERSONAL_ID = '284';
	/* certificate validity period */
	public static $CAPI_VALID_DAYS = '395';
	/* constants for the test-mode. These will go into the certificate subject in online mode */
	public static $CAPI_TEST_DC_PREFIX = 'TEST CERTIFICATE';
	public static $CAPI_TEST_O_PREFIX = 'TEST UNIVERSITY ';
	public static $CAPI_TEST_CN_PREFIX = 'TEST PERSON ';
	/* certificate validity period in test mode */
	public static $CAPI_TEST_VALID_DAYS = '14';

	/* Limit the file endings that are going to be accepted.
	 * There can be images with embedded comments. As the comments can
	 * contain PHP code, allowing files with suffix .php is dangerous,
	 * even when a check for the file mime-type has already been made.
	 * Classical injection scenario.
	 */
	public static $ALLOWED_IMG_SUFFIXES = array('png','jpg','gif');
	/*
	 * the length of the auth_key, as it is used in standalone signing */
	public static $AUTH_KEY_LENGTH = '40';
}

?>
