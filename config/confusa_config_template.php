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
	'approve'		=> '/key_handler.php',

        /* For CA handling */
        'standalone'            => True, /* true: no extra CA, use php to sign
                                          * key */

        'ca_host'               => 'localhost',
        'ca_port'               => '9443',
	'ca_cert_name'		=> '',
	'ca_cert_path'		=> 'certs/',

	/* OU and O for the certificate */
	'cert_o'		=> '',
	'cert_ou'		=> '',

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
	'auth_var'		=> 'auth_key', /* the variable to pass as the
						* authentication-url we use for
						* authenticating users' CSRs */

	'auth_length'		=> '16', /* length of auth-url token */

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


        /* if we should use the sms-layer at all  */
	'use_sms'                       => false,

        /* should we include debug-behaviour in sms-class? */
        'sms_debug'                     => true,


        /* pr. default we send email to an sms-server (that's the level of sms
         * supported in this edition. the system expects the sms-server to
         * accept emails as the following oneliner will do from a cmd-line:
         * echo "your message" | mail -s "phonenumber" "sms_gw_email@address"
         */
	'sms_gw_addr'                   => null,

        /* how long should a SMS-password be valid before a new one must be
         * generated and sent to the user? */
	'sms_pw_timeout'		=> 15,

        /* when the user has authenticated via SMS-pw, how long should the
         * session be valid (between page refreshes) */
	'sms_session_timeout'           => 30,

        /* the from-addr to show up in the emails from the system */
	'sys_from_address'		=> 'your@system.contact.addr',


	/* CSR upload limits. This is not strictly enforced yet */
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
