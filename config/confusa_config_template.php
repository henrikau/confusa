<?php
  /* Author: Henrik Austad <henrik.austad@uninett.no>
   * July 2008
   */
$confusa_config = array(
	/* global config-flag
	 * Set this to true to enable debug-logging, extra output etc
	 */
	'debug'			=> true,

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
         * The path whould point to where simpleSAMLphp's _include.php resides
         */
	'simplesaml_path'	=> '/var/www/simplesamlphp/www/_include.php',

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

        /* For CA handling */
        'standalone'            => True, /* true: no extra CA, use php to sign
                                          * key */
        /* ca_host and ca_port can be removed */
        'ca_host'               => 'localhost',
        'ca_port'               => '9443',
                                        /* The following fields can be used when the Comodo-API is called
                                         * for certificate creation */
        'capi_apply_endpoint'          => 'https://secure.comodo.com/products/!applyCustomClientCert',
        'capi_auth_endpoint'           => 'https://secure.comodo.net/products/!AutoAuthorize',
        'capi_collect_endpoint'        => 'https://secure.comodo.net/products/download/CollectCCC',
        'capi_revoke_endpoint'              => 'https://secure.comodo.net/products/!AutoRevokeCCC',
        'capi_ap_name'                 => '',
        'capi_escience_id'                      => '285',
        /* if we ever want to issue e-mail certificates */
        'capi_personal_id'                      => '284',
        /* will insert a 'TEST' string into the certificate subjects if set to true */
        'capi_test'                             => true,
        /* will encrypt the (sub)-account passwords in the DB with this key */
        'capi_enc_pw'                           => '',
	'ca_cert_name'		=> '',
	'ca_cert_path'		=> 'cert_handle/certs/',
	'ca_key_name'		=> '',
	'ca_key_path'		=> 'cert_handle/priv/',

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
        'cert_default_timeout'           => '0 0:15:0',

        /* how long a CSR should stay in the csr_cache before being
         * removed. Time in MySQL-format
         * Time should be fairly low as you don't want the database cluttered
         * with CSRs.
         */
        'csr_default_timeout'            => '0 0:10:0',


	/* this should be set to true when config is verified (or the file has
	 * been updated and not just copied)
	 * This should also find all the users that doesn't read the config file
	 * properly ;-)
	 */
	'valid_install'		=> false
	);
?>
