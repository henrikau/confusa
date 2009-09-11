<?php
  /* Author: Henrik Austad <henrik.austad@uninett.no>
   * July 2008
   */
$confusa_config = array(
	/* global config-flag
	 * Set this to true to enable debug-logging, extra output etc
	 */
	'debug'			=> true,

	/* maintenance switch
	 *
	 * When this is set to true, Confusa will enter Maintenance mode,
	 * showing a default 'under maintenance, check back later' message to
	 * all users.
	 *
	 * It will primarily be used by scripts and should not be
	 * manually set to anything but false (unless you have a very good
	 * reason to do so, and you know what you're doing (-; ).
	 */
	'maint'			=> false,

        /* install path */
        'install_path'                  => '/var/www/confusa/',

	/* script variables, where the end-user create-keyresides */
	'programs_path'		=> '/var/www/confusa/programs/create_cert.sh',


        /* The url to the server
         *
         * This is possible to deduce automatically, but PHP has a few quircks,
         * so to be sure that it is set correctly, give the proper path here
         *
         * i.e.
         * 'server_url'		=> 'https://your.server.com/path/',
         */
	'server_url'		=> null,


        /* Pr. default, confusa uses simpleSAMLphp for authentication
         * You can use something else, but you must edit quite a few files to
         * make this possible.
         *
         * The path whould point to the root of the simpleSAMLphp install directory
         */
	'simplesaml_path'	=> '/var/www/simplesamlphp/',

	/* smarty path
	 *
	 * This is the absolute path to the file
	 *
	 *	Smarty.class.php
	 *
	 * normally found in
	 *
	 *	/usr/share/php/smarty/
	 *
	 */
	'smarty_path'		=> '/usr/share/php/smarty/',

	/* for script CSR/cert-handling
         *
         * key_upload is the program that handles automatic CSR upload from the user-script.
         * key_download is the corresponding download of signed certificate
         *
         * Unless you are willing to recreate a lot of Confusa, you should not
         * change this.
         */
        'upload'                => '/key_upload.php',
        'download'              => '/key_download.php',
	'approve'		=> '/index.php',
	/* the page of Confusa that should be shown after the user signed in */
	'post_login_page'	=> '/about_nren.php',

	/* for NREN landing page customization
	 *
	 * define where custom CSS files and logos are kept - the paths are
	 * relative to Confusa's' www directory
	 */
	'custom_css'		=> 'css/custom/',
	'custom_logo'		=> 'graphics/custom/',

        /* For CA handling.
         * Legal modes are: CA_STANDALONE and CA_ONLINE */
	'ca_mode'		=> CA_STANDALONE,

	/* The following fields can be used when the Comodo-API is called
	 * for certificate creation */
        'capi_apply_endpoint'          => 'https://secure.comodo.com/products/!applyCustomClientCert',
        'capi_auth_endpoint'           => 'https://secure.comodo.net/products/!AutoAuthorize',
        'capi_collect_endpoint'        => 'https://secure.comodo.net/products/download/CollectCCC',
        'capi_listing_endpoint'             => 'https://secure.comodo.net/products/!Tier2ResellerReport',
        'capi_revoke_endpoint'              => 'https://secure.comodo.net/products/!AutoRevokeCCC',
        'capi_escience_id'                      => '285',
        /* if we ever want to issue e-mail certificates */
        'capi_personal_id'                      => '284',
        /* will insert a 'TEST' string into the certificate subjects if set to true */
        'capi_test'                             => true,
        /* will encrypt the (sub)-account passwords in the DB with this key */
        'capi_enc_pw'                           => '',
	'capi_root_cert'			=> 'http://crt.tcs.terena.org/TERENAeSciencePersonalCA.crt',
	'capi_crl'				=> 'http://crl.tcs.terena.org/TERENAeSciencePersonalCA.crl',

	/* Values needed for standalone-mode
	 * The names should be self-explanatory. All paths are relative to the
	 * install_path
	 */
	'ca_cert_base_path'	=> '/cert_handle',
	'ca_cert_path'		=> '/certs',
	'ca_cert_name'		=> '',
	'ca_key_path'		=> '/priv',
	'ca_key_name'		=> '',
	'ca_conf_name'		=> '/conf/confusa_openssl.cnf',
	'ca_crl_name'		=> '/confusa_crl.pem',

        /* this *should* be true, as you really* want wget to detect a
         * SSL-man-in-the-middle attac! However, as a workaround for testsystems
         * (which normally does not hav e properly signed SSL-certificate),
         * force user-script to disregard invalid/self-signed certs. */
	'script_check_ssl'	=> False,

        /* default length of client key. This is minimum keylength, a user can
         * upload a longer key, if he/she wants that */
	'key_length'		=> '2048',

        /* where to report errors in script for users. This should be set to
         * something sane for *your* installation */
	'error_addr'		=> 'your@error.addr',

	'csr_var'		=> 'remote_csr', /* name of the variable that
						  * the upload-handler checks
						  * for csr's */
	'auth_var'		=> 'inspect_csr', /* the variable to pass as the
						* authentication-url we use for
						* authenticating users' CSRs */

	'auth_length'		=> '40', /* length of auth-url token */

	/* logs */
	'default_log'		=> '/var/log/confusa/tmp.log',
	'loglevel_min'		=> LOG_DEBUG, /* see syslog (php) for details */
	'syslog_min'			=> LOG_DEBUG, /* ... */


	/* mysql-variables */
	'mysql_username'		=> 'webuser',
	'mysql_password'		=> null,
	'mysql_host'			=> 'localhost',
	'mysql_db'			=> 'confusa',

        /* where should backup of the database be stored */
        'mysql_backup_dir'              => '/var/backups',


	/* The name of the System. This is the prefix of all titles. For
	 * instance, process_csr.php sets this to be 'Process CSR'
	 * The resulting title (<TITLE>) will then be: "Confusa - Process CSR"
	 */
	'system_name'		=> 'Confusa',

        /* the from-addr to show up in the emails from the system */
	'sys_from_address'		=> 'your@system.contact.addr',


	'remote_retries'		=> 10, /* the number of CSRs a user can
						* upload before he/she must log
						* in and clean up.  */
	'remote_ips'			=> 5, /* how many different CSRs can
					       * exist in the database at any
					       * given time, uploaded from the
					       * SAME ip-address. If this
					       * number is high, it can
					       * indicate someone trying to
					       * spam down the database. */

        /* how long should a certificate be valid in the cert_cache before being
         * doomed expired (to avoid that it's available for a long time for the
         * world) */
        'cert_default_timeout'           => array(15, 'MINUTE'),

        /* how long a CSR should stay in the csr_cache before being
         * removed. Time consists of an array with the amount being the first
         * entry and the time-unit being the second entry
         * Time should be fairly low as you don't want the database cluttered
         * with CSRs.
         */
        'csr_default_timeout'            => array(10, 'MINUTE'),

	/* protected_session_timeout
	 *
	 * When the user is about to do something critical, e.g. revoking or
	 * applying for a certificate, it is important to make sure that the
	 * user AuthN 'recently'.
	 */
	'protected_session_timeout'	=> '10',

	/**
	 * When set to true this variable will bypass simplesaml and create fake attributes
	 * so that the site can be tested without authentication
	 *
	 * The auth-bypass should be off by default:config/confusa_config_template.php
	 */
	'auth_bypass'		=> false,

	/*
	 * Languages available and what language is default
	 */
	'language.available'	=> array('en', 'sv', 'de', 'lb'),
	'language.default'	=> 'en',

	/* entitlement namespace
	 *
	 * This is to allow an instance to configure another namespace. This
	 * should be the entire string except the actual attribute.
	 *
	 * If the entire attribute is
	 *
	 *	urn:mace:some.idp.org:some.sub.domain:confusa
	 *
	 * the namespace-value should be:
	 *
	 *	urn:mace:some.idp.org:some.sub.domain
	 */
	'entitlement_namespace'	=> null,

	/* this should be set to true when config is verified (or the file has
	 * been updated and not just copied)
	 * This should also find all the users that doesn't read the config file
	 * properly ;-)
	 */
	'valid_install'		=> false
	);
?>
