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
	 * Comodo certificate signing. The Comodo API documentation can be found at
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
	public static $CAPI_CRL = 'http://crl.tcs.terena.org/TERENAeSciencePersonalCA.crl';
	/* used in the API */
	public static $CAPI_ESCIENCE_ID = '285';
	/* if we ever want to issue e-mail certificates */
	public static $CAPI_PERSONAL_ID = '284';
	/* eScience certificate validity period */
	public static $CAPI_VALID_ESCIENCE = '395';
	/* personal certificate validity period */
	public static $CAPI_VALID_PERSONAL = array('365', '730', '1095');
	/* constants for the test-mode. These will go into the certificate when
	 * using the Comodo CA */
	public static $CAPI_TEST_DC_PREFIX = 'TEST CERTIFICATE';
	public static $CAPI_TEST_O_PREFIX = 'TEST UNIVERSITY ';
	public static $CAPI_TEST_CN_PREFIX = '';
	/* certificate validity period in test mode */
	public static $CAPI_TEST_VALID_DAYS = '14';

	/* Limit the file endings that are going to be accepted.
	 * There can be images with embedded comments. As the comments can
	 * contain PHP code, allowing files with suffix .php is dangerous,
	 * even when a check for the file mime-type has already been made.
	 * Classical injection scenario.
	 */
	public static $ALLOWED_IMG_SUFFIXES = array('png','jpg','gif');

	public static $ORG_STATES = array('subscribed', 'suspended', 'unsubscribed');
	/* CRL reason codes according to RFC 3280. */
	public static $REVOCATION_REASONS = array('unspecified',
	                                          'keyCompromise',
	                                          'affiliationChanged',
	                                          'superseded',
	                                          'certificateHold',
	                                          'privilegeWithdrawn',
	                                          'aACompromise');
	/*
	 * STANDALONE constants
	 * the length of the auth_key, as it is used in standalone signing */
	public static $AUTH_KEY_LENGTH = '40';
	public static $OPENSSL_SERIAL_FILE='/var/lib/confusa/cert/ca.db.srl';
	/* since the ca.db is in /var/lib/confusa/cert as well, define the crl
	 * as a constant as well to avoid cluttering of the whole system with
	 * different pieces of Confusa's signing process
	 */
	public static $OPENSSL_CRL_FILE='/var/lib/confusa/cert/confusa.crl';
	/* The "authority" (i.e. authentication source) upon which SimpleSAML
	 * should fallback if none is detected in the session.
	 * (NB: Since simplesamlphp is supposed to handle this, generally the
	 * "fallback" should only happen when there is no authN session at all)
	 */
	public static $DEFAULT_SESSION_AUTHORITY = 'saml2';
	/* this array is used when mapping from UTF8 characters to ASCII characters
	 * (for instance when constructing the eScience DN from the attributes)
	 */
	public static $UTF8_ASCII_MAP = array(
		'Å' => 'aa',
		'å' => 'aa',
		'Æ' => 'Ae',
		'æ' => 'ae',
		'Ä' => 'Ae',
		'ä' => 'ae',
		'Č' => 'C',
		'č' => 'c',
		'Ď' => 'D',
		'ď' => 'd',
		'ð' => 'th',
		'Þ' => 'Th',
		'þ' => 'th',
		'É' => 'E',
		'é' => 'e',
		'È' => 'E',
		'è' => 'e',
		'Ë' => 'E',
		'ë' => 'e',
		'Ě' => 'E',
		'ě' => 'e',
		'Í' => 'I',
		'í' => 'i',
		'Ň' => 'N',
		'ň' => 'n',
		'Ö' => 'Oe',
		'ö' => 'oe',
		'Ø' => 'Oe',
		'ø' => 'oe',
		'Ó' => 'O',
		'ó' => 'o',
		'Ř' => 'R',
		'ř' => 'r',
		'Š' => 'S',
		'š' => 's',
		'ß' => 'sz',
		'Ť' => 'T',
		'ť' => 't',
		'Ü' => 'Ue',
		'ü' => 'ue',
		'Ú' => 'U',
		'ú' => 'u',
		'Ů' => 'U',
		'ů' => 'u',
		'Ý' => 'Y',
		'ý' => 'y',
		'Ž' => 'Z',
		'ž' => 'z'
	);

	/* where the compiled smarty classes get stored. Should be writable by
	 * webserver user, hence it should not be in the normal directory tree */
	public static $SMARTY_TEMPLATES_C = '/var/cache/confusa/templates_c';
	public static $SMARTY_CACHE = '/var/cache/confusa/smarty_cache/';
	public static $ZIP_CACHE    = '/tmp/';

	/* name of the eScience product */
	public static $ESCIENCE_PRODUCT = 'TCS eScience Personal';
	/* name of the personal certificate product */
	public static $PERSONAL_PRODUCT = 'TCS Personal';

	/* logging headers */
	public static $LOG_HEADER_DEBUG = 'debug:';
	public static $LOG_HEADER_INFO = 'info:';
	public static $LOG_HEADER_NOTICE = 'notice:';
	public static $LOG_HEADER_WARNING = 'WARNING:';
	public static $LOG_HEADER_ERR = ' ERROR:';
	public static $LOG_HEADER_CRIT = ' -= CRITICAL =-';
	public static $LOG_HEADER_ALERT = ' -= [ ALERT ] =-';
	public static $LOG_HEADER_EMERG = ' EMERG EMERG EMERG';

	/* positions at which a logo can be placed in the branding process */
	public static $ALLOWED_LOGO_POSITIONS = array('tl',  /* top left */
	                                              'tc',  /* top center */
	                                              'tr',  /* top right */
	                                              'bg',  /* background */
	                                              'bl',  /* bottom-left */
	                                              'bc',  /* bottom center */
	                                              'br'); /* bottom-right */

	/* use the same name as the PHP-SESSION to avoid crashing simpleSAMLphp */
	public static $SESSION_NAME = "PHPSESSID";
}

?>
