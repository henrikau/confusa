#!/bin/bash
# Confusa auto-setup.
#
# GPL v3 applies

# Try to find the config directory. Depending on whether Confusa was
# downloaded from the Git repository or installed from the Debian
# package, that is in the Confusa directory or in /etc/confusa/config/

# Determine path, move to basefolder of confusa
if [ `basename $0` != $0 ]; then
    cd `dirname $0`
fi

# get install-lib functions
if [ -f "../lib/bash/install_lib.sh" ]; then
    . ../lib/bash/install_lib.sh
fi
if [ -f "../lib/bash/config_lib.sh" ]; then
    . ../lib/bash/config_lib.sh
fi

if	[ -d "../config/" ] &&
	[ -f "../config/confusa_config_template.php" ]; then
	prefix="../config/"

elif	[ -d "/etc/confusa" ] &&
		[ -f "/etc/confusa/confusa_config_template.php" ]; then
	prefix="/etc/confusa/"
else
	echo -n "Could not find config directory! Tried ../config/ and "
	echo "/etc/confusa/config/!"
	exit 1
fi

constants="../lib/confusa_constants.php"
config_template=${prefix}"confusa_config_template.php"
working_template=`mktemp /tmp/.confusa_wrk_template_XXXXXX`
# The template written by the debian configuration helper dbconfig-common
dbconfig_template="/etc/confusa/confusa_config.inc.php"
config=${prefix}"confusa_config.php"

# replace_interval_in_config
#
# TODO: proper description of function
function replace_interval_in_config
{
    LEGAL_VALUES=("SECOND" "MINUTE" "HOUR" "DAY" "WEEK" "MONTH" "YEAR")

    while [[ -z $unit  || -z $value ]]; do
	msg="Please specify the format for \"$1\" in the notation \$time \$unit"
	msg="$msg where unit can be one of (SECOND, MINUTE, HOUR, DAY, WEEK, "
	msg="$msg MONTH, YEAR) [$2 $3]: "
	read -p "$msg" tmp_value, tmp_unit

	# values not set, use default values.
	if [ -z "$tmp_value" ]; then
	    tmp_value=${2}
	fi
	if [ -z "$tmp_unit" ]; then
	    tmp_unit=${3}
	fi

	# Check if the value is a number
	if [ $tmp_value -eq $tmp_value 2> /dev/null ]; then
	    value=${tmp_value}
	else
	    echo "Not a valid number ($tmp_value). Please try again."
	    continue;
	fi

	# is the supplied unit in the range of legal values?
	for (( i=0;i<${#LEGAL_VALUES[@]};i++ )); do
	    if [ $tmp_unit = ${LEGAL_VALUES[${i}]} ]; then
		unit=${tmp_unit}
		break
			fi
	done
    done # while either unit or value are zero

    # FIXME: use replace_config_entry at this point?
    sed s\|"'$1'[ \t]*=>.*"\|"'$1'		=> array($value,'$unit'),"\| < $working_template > $config
    cp $config $working_template
    unit=""
    value=""
}

# configure_confusa_settings
#
# Main control loop. This is where the main part of the configuration happens.
function configure_confusa_settings
{
	cp $config_template $working_template
	msg="
	==================================================================

	We will walk you through the configuration of the most important
	Confusa settings. The idea is to get a working basic Confusa
	instance. You can configure Confusa in a more fine-grained way by
	editing confusa_config.php in Confusa's config directory.

	Press any key to continue to continue or wait 20 seconds

	==================================================================
	"
	read -p "$msg" -n1 -t20 any_key

	###############################################################################
	# Config flags deliberately not included in the installer script
	#
	# Some flags were not included because they are "too advanced".
	# Others were left out for the sake of simplicity, to keep basic setup
	# fast and clean
	#
	#
	# debug					- Should be set to false in the template
	# maint					- Does not make sense in initial configuration
	# custom_css			- advanced
	# custom_logo			- advanced
	# capi_test				- should be set to false in the template
	# ca_cert_base_path
	# ca_cert_path
	# ca_cert_name
	# ca_key_path
	# ca_key_name
	# ca_conf_name
	# loglevel_min
	# syslog_min			- when you dig into logfiles, you can also change config files
	# auth_bypass			- not supposed to change, set false in template
	# language.availabe		- set to something reasonable in template
	# language.default		- set to 'en' in template, if somebody wants to change => config file
	#
	###############################################################################

	# Confusa's operation mode
	###############################################################################
	echo "

	Confusa can operate in two modes. The first one is standalone,
	in which Confusa will use its own installed CA to sign certificates for
	the user, the other one is online, in which a hooked up remote CA will
	be used for signing certificate request, revocation etc. Currently this
	Online-CA is Comodo. In which mode do you wish to operate Confusa?"
	select mode in comodo standalone; do
		case $mode in
			"standalone") ca_mode="CA_STANDALONE" ;;
			"comodo") ca_mode="CA_COMODO" ;;
			*) continue ;;
		esac

		# need the mode without exclamation marks, because it is an enumeration
		replace_config_entry_raw 'ca_mode' $ca_mode
		break
	done

	echo ""

	# Autoconfigure:
	# set some config flags to feasible values
	###############################################################################
	if [ $mode = "comodo" ]; then
		# sed s\|"'capi_test'[ \t]*=>.*"\|"'capi_test'    => false,"\| < $working_template > $config
		# cp $config $working_template
	    replace_config_entry_raw 'capi_test' false
	elif [ $mode = "standalone" ]; then
		# hardcode for the sake of simplicity
		# c'mon don't be picky :)
	    echo "
	    You have chosen standalone.
	    For simplicity, the key and certificate has been set to:
		'ca_cert_name'\t=> 'servercert.pem'
		'ca_key_name'\t=> 'serverkey.pem'
	    This means that the key and certificate will be found under in these names cert_handle/.
	    If this is wrong, change the configuration."
	    replace_config_entry "ca_cert_name" "servercert.pem"
	    replace_config_entry "ca_key_name"  "serverkey.pem"
	fi

	# eScience or personal mode
	############################################################################
	echo "

	Confusa can issue eScience (Grid) and personal certificates.
	Personal certificates are different from eScience certificates in
	that they have another signing-CA, a configurable validity period
	between 365 and 1095 days and usually also allow UTF-8 characters in
	their subject-DN. In which of these modes should Confusa operate?"
	select mode in escience personal; do
		case $mode in
			"escience") product="PRD_ESCIENCE" ;;
			"personal") product="PRD_PERSONAL" ;;
			*) continue ;;
		esac

		# need the mode without exclamation marks, because it is an enumeration
		replace_config_entry_raw 'cert_product' $product
		break
	done

	replace_config_entry_raw 'debug' false
	replace_config_entry_raw 'maint' false
	replace_config_entry_raw 'auth_bypass' false
	replace_config_entry "language.default" "en"
	replace_config_entry "default_log" "/var/log/confusa.log"

	# Guess the installation path of Confusa to use it as default (assuming bash)
	#############################################################################
	script_dir=`pwd`
	install_path=`echo | awk -v sdir=$script_dir '{sub("bin", "", sdir); print sdir}'`

	read -p "Confusa install path: [$install_path]: " custom_install_path
	# don't be tricked by erroneous input
	while [ ! -d "$custom_install_path" ]; do
		if [ -z $custom_install_path ]; then
			custom_install_path=$install_path
		else
			read -p "Confusa install path must be a directory [$install_path]: " \
			    custom_install_path
		fi
	done
	single_trailing_slash $custom_install_path
	custom_install_path=$res
	# The path to the program for the key generation script
	replace_config_entry "install_path" $custom_install_path
	echo ""

	# Configure the server url
	###############################################################################
	burl="https://www.example.org"
	bpath="/confusa/"
	while [ 1 == 1 ]; do
	    read -p "Please enter the URL of the server with Confusa installed: (e.g. $burl ): " \
		server_url
	    # sloppily check if that thingie looks remotely like a URL
	    server_url=`test_url $server_url`
	    if [ -z $server_url ]; then
		server_url=$burl
	    fi

	    # Remove any trailing slash
	    server_url=${server_url%/}

	    echo ""

	    # get folder-path
	    read -p "Please enter the path to Confusa on your server (e.g. $bpath): " \
		server_path
	    if [ -z $server_path ]; then
		server_path=$bpath
	    fi
	    has_leading_slash=`echo $server_path | grep "^/"`
	    if [ -z $has_leading_slash ]; then
		server_path=/${server_path}
	    fi

	    server_path=`single_trailing_slash $server_path`
	    echo ""

	    get_user_alternative "The full path to Confusa is ${server_url}${server_path}? [Y/n]" "Y"

	    # user is happy, terminate
	    if [[ $answer = "y" || $answer = "Y" ]] && [[ ! -z $server_url && ! -z $server_path ]]; then
		break
	    else
		burl=$server_url
		bpath=$server_path
	    fi
	done

	replace_config_entry "server_url" ${server_url}${server_path}
	echo ""

	# Configure the path to simplesamlphp
	# Try to guess the simplesaml-path first, if that's not possible fall back
	# to the setting in the template
	###############################################################################
	SIMPLESAML_GUESS=("/usr/share/simplesamlphp/" "/var/www/simplesamlphp" "/var/simplesamlphp/" "/usr/share/simplesaml/" "/var/www/simplesaml" "/var/simplesaml")

	for (( i=0;i<${#SIMPLESAML_GUESS[@]};i++ )); do
		if [ -d ${SIMPLESAML_GUESS[${i}]} ]; then
			simplesaml_path=${SIMPLESAML_GUESS[${i}]}
			break
		fi
	done

	if [ -z $simplesaml_path ]; then
		simplesaml_path=`grep "'simplesaml_path'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`
	fi

	# Get the path, continue to batter the user until a valid path is given.
	read -p "Please enter the path to simplesamlphp [$simplesaml_path]: " \
	    custom_simplesaml_path
	while [ ! -d "$custom_simplesaml_path" ]; do
		if [ -z $custom_simplesaml_path ]; then
			custom_simplesaml_path=$simplesaml_path
		else
			read -p "Need a directory for the simplesaml path [$simplesaml_path]: " \
			    custom_simplesaml_path
		fi
	done
	# make sure the path as a single trailing slash. The function
	# saves the result in $res
	single_trailing_slash $custom_simplesaml_path
	replace_config_entry "simplesaml_path" $res
	echo ""

	# Configure the path to smarty
	# Use the two know locations (debian and ubuntu) to start with.
	###############################################################################
	SMARTY_GUESS=("/usr/share/php/smarty/" "/usr/share/php/smarty/lib/")
	for (( i=0;i<${#SMARTY_GUESS[@]};i++ )); do
		if [ -f ${SMARTY_GUESS[${i}]}/Smarty.class.php ]; then
			smarty_path=${SMARTY_GUESS[${i}]}
			echo "Found smarty-path at $smarty_path"
			break
		fi
	done

	# If we cant find the path, use / (the default in the template
	# is the ubuntu-flavour, which has been covered by the
	# auto-detect routine
	if [ -z $smarty_path ]; then
	    smarty_path="/"
	fi
	read -p "Please enter the path to the PHP template engine smarty [$smarty_path]: "\
	    custom_smarty_path

	while [ ! -d "$custom_smarty_path" ]; do
		if [ -z $custom_smarty_path ]; then
			custom_smarty_path=$smarty_path
		else
			read -p "Need a directory for the smarty path! [$smarty_path]: " \
			    custom_smarty_path
		fi
	done
	# Make sure only a single slash remains (function saves result in $res)
	single_trailing_slash $custom_smarty_path
	replace_config_entry "smarty_path" $res
	echo ""

	# COMODO: generate a password with which the login password will be protected
	# if pwgen is not installed, then ask for a password
	###############################################################################
	if [ $mode == "comodo" ]; then
		have_pwgen=`which pwgen`

		if [ "$?" -eq "0" ]; then
			echo "Generating a password for encryption of the Comodo credentials"
			capi_enc_pw=`pwgen -1 -s -n 12`
			res=$?
		fi

		if [ -z $have_pwgen ] || [ ! $res -eq 0 ]; then
			echo -n "Please enter a password for the encryption of Comodo credentials: "
			stty -echo
			read capi_enc_pw
			stty echo
			echo ""
		fi

		replace_config_entry "capi_enc_pw" $capi_enc_pw
	fi
	echo ""


	# Specify the minimum keylength for Confusa
	# If nothing is found in the template, use a hardcoded value of 1024.
	###############################################################################
	get_from_config_template "key_length"
	if [ -z $res ]; then
	    key_length=$res
	    else
	    key_length="1024"
	fi
	echo "
Specify the minimum key length in bits for Confusa-issued certificates.

This is important as it determins how strong the keys *must* be. Note
that this will not stop users from creating keys that are even longer."

	read -p "(It is recommended to have a key length >= 1024) [$key_length]: " \
	    custom_key_length

	# basic check on the key length
	while [ 1 -eq 1 ]; do
		if [ -z $custom_key_length ]; then
			custom_key_length=$key_length
			break
		else
			case $custom_key_length in
				512|1024|2048|4096) break ;;
			esac
			read -p "Key length must be one of 512, 1024, 2048 and 4096 [$key_length]: " \
			    custom_key_length
		fi
	done
	replace_config_entry "key_length" $custom_key_length
	echo ""

	# Skip the DB-name, host, username and password configuration, if a config
	# file has been written by dbconfig-common
	###############################################################################
	if [ ! -f $dbconfig_template ]; then
	    # Configure the mysql username
	    get_from_config_template "mysql_username"
	    mysql_username=$res

	    read -p "The user-name for accessing the MySQL-DB [$mysql_username]: " \
		custom_mysql_username

	    if [ -z $custom_mysql_username ]; then
		custom_mysql_username=$mysql_username
	    fi
	    replace_config_entry "mysql_username" $custom_mysql_username
	    echo ""

	    # Configure the mysql password
	    if [ which pwgen > /dev/null ]; then
			echo "Generating mysql password with pwgen..."
			mysql_password=`pwgen -1 -n 12 -s`
	    fi
	    if [ -z $mysql_password ] || [ ! $? -eq 0 ]; then
		while [ -z $mysql_password ]; do
		    echo "Please specify a password for the user $custom_mysql_username "
		    echo -n "for MySQL:"
		    stty -echo
		    read mysql_password
		    stty echo
		    echo ""
		done
	    fi
	    replace_config_entry "mysql_password" $mysql_password
	    echo ""

	    # Configure the mysql-host
	    get_from_config_template 'mysql_host'
	    mysql_host=$res

	    read -p "The host on which mysql is to run [$mysql_host]: " \
		custom_mysql_host

	    if [ -z $custom_mysql_host ]; then
		custom_mysql_host=$mysql_host
	    fi

	    replace_config_entry "mysql_host" $custom_mysql_host
	    echo ""

	    # Configure the mysql-DB-name
	    get_from_config_template 'mysql_db'
	    mysql_db=$res

	    read -p "Enter DB (name) which should be used for Confusa [$mysql_db]: " \
		custom_mysql_db

	    if [ -z $custom_mysql_db ]; then
		custom_mysql_db=$mysql_db
	    fi

	    replace_config_entry "mysql_db" $custom_mysql_db
	    echo ""
	else
	    echo "Using database settings written by dbconfig-common..."
	fi # dbconfig-template exists

	# Configure the mysql-backup dir
	###############################################################################
	get_from_config_template 'mysql_backup_dir'
	mysql_backup_dir=$res

	read -p "Specify the directory in which backups of the MySQL-DB are stored [$mysql_backup_dir]: " \
	    custom_mysql_backup_dir

	while [ ! -d "$custom_mysql_backup_dir" ]; do
		if [ -z $custom_mysql_backup_dir ]; then
			custom_mysql_backup_dir=$mysql_backup_dir
		else
			echo -n "Backup dir must be a directory [$mysql_backup_dir]: "
			read custom_mysql_backup_dir
		fi
	done

	replace_config_entry "mysql_backup_dir" $custom_mysql_backup_dir
	echo ""

	# Configure the system name
	###############################################################################
	get_from_config_template 'system_name'
	system_name=$res

	echo "Enter the name of the system. "
	read -p "This name will appear in titles in the browser [$system_name]: " \
	    custom_system_name

	if [ -z "$custom_system_name" ]; then
		custom_system_name="$system_name"
	fi

	replace_config_entry "system_name" "${custom_system_name}"
	echo ""

	# Configure the sys_from address
	###############################################################################

	while [ -z $custom_sys_from_address ]; do
		read -p "Configure the address that shows up in mails from the system: " \
		    custom_sys_from_address

		# Sloppily check if that thingie remotely ressembles a mail address
		test_email $custom_sys_from_address
		custom_sys_from_address=$res
	done

	# Set both sys_from_address and sys_header_from_address to the same value.
	# If the user wants more fine-grained setup, he/she can always change
	# confusa_config.php
	replace_config_entry "sys_from_address" $custom_sys_from_address
	replace_config_entry "sys_header_from_address" $custom_sys_from_address
	echo ""

	# Configure the cert-default-timeout
	##############################################################################
	if [ $mode = "standalone" ]; then
	    # cannot use the get_from_config_template here, as the
	    # result is stored in an array
	    cert_default_timeout_value=`grep "'cert_default_timeout'" $working_template | cut -d '=' -f 2 \
		| cut -d '(' -f 2 | cut -d ',' -f 1`
	    get_from_config_template 'cert_default_timeout'
	    cert_default_timeout_unit=res

	    echo "Specify the timeout for certificates, i.e. the interval within which"
	    echo "they will be kept available for download to the user."
	    echo ""
	    replace_interval_in_config "cert_default_timeout" \
		$cert_default_timeout_value \
		$cert_default_timeout_unit
	    echo ""
	fi

	# Configure the CSR-default-timeout
	###############################################################################
	csr_default_timeout_value=`grep "'csr_default_timeout'" $working_template | cut -d '=' -f 2 \
				| cut -d '(' -f 2 | cut -d ',' -f 1`
	get_from_config_template 'csr_default_timeout'
	csr_default_timeout_unit=$res

	echo "Specify the timeout for CSRs, i.e. the interval within which the user "
	echo "will be able to authorize and view them. "
	echo ""
	replace_interval_in_config "csr_default_timeout" \
	    $csr_default_timeout_value \
	    $csr_default_timeout_unit
	echo ""

	# Configure the protected session timeout
	###############################################################################
	get_from_config_template 'protected_session_timeout'
	protected_session_timeout=$res

	echo "The protected session timeout default value is: \
	    $custom_protected_session_timeout"

	custom_protected_session_timeout=""

	while [ 1 == 1 ]; do
		echo "How long should the session allow the user to \
		    perform \"sensitive\" actions "
		read -p "(in minutes) [$protected_session_timeout]: " \
		    custom_protected_session_timeout

		if [ -z $custom_protected_session_timeout ]; then
			custom_protected_session_timeout=$protected_session_timeout
			break
		fi

		# Check if the session timeout is a number
		if [ $custom_protected_session_timeout -eq $custom_protected_session_timeout 2> /dev/null ]; then
			break
		else
			continue
		fi
	done

	replace_config_entry "protected_session_timeout" $custom_protected_session_timeout
	echo ""

	# Set valid install to true and remove the working copy of the template.
	###############################################################################
	replace_config_entry_raw "valid_install" true
	rm $working_template

} # end configure_confusa_settings


# postinstall_standalone()
#
# This only applies for systems that are placed in standalone-mode
#
# configure the directories and permissions for the installed CA
# Offer the possibility to copy a cert/private key to these locations
function postinstall_standalone
{
    custom_apache_user=$1
    install_path=$2
    if ! ca_cert_base_path=`_get_config_entry 'ca_cert_base_path'`; then
	echo "did not find ca_cert_base_path in config $config"
    fi
    if ! ca_cert_path=`_get_config_entry 'ca_cert_path'`; then
	echo "did not find ca_cert_path in config $config"
    fi
    if ! ca_cert_name=`_get_config_entry 'ca_cert_name'`; then
	echo "did not find ca_cert_name in config $config"
    fi
    if ! ca_key_path=`_get_config_entry 'ca_key_path'`; then
	echo "did not find ca_key_path in config $config"
    fi
    if ! ca_key_name=`_get_config_entry 'ca_key_name'`; then
	echo "did not find ca_key_name in config $config"
    fi

    # CRL_info (from the constants)
    crl_path=`grep "OPENSSL_CRL_FILE" $constants | cut -d '=' -f 2 | cut -d "'" -f 2 | cut -d "'" -f 1`
    ca_write_dir=`dirname $crl_path`

    # Create directories for the CA-parts
    mkdir -p ${install_path}${ca_cert_base_path}/${ca_cert_path} &&\
    mkdir -p ${install_path}${ca_cert_base_path}/${ca_key_path}  && \
    mkdir -p $ca_write_dir

    if [ ! $? -eq 0 ]; then
	echo "Error creating the directories for the CA"
	echo "Directories:"
	echo "CA certificate: " ${install_path}${ca_cert_base_path}/${ca_cert_path}
	echo "CA key: " ${install_path}${ca_cert_base_path}/${ca_key_path}
	echo "CA write-dir: ${ca_write_dir})"
    fi

    get_user_alternative "Do you want to copy a certificate/key pair for signing from your filesystem to Confusa (Y/n)?" "Y"

    if [[ $answer = "y" || $answer = "Y" ]] && \
	[[ -n $ca_key_name && -n $ca_cert_name ]]; then
	# get and copy the certificate into Confusa's cert-directory
	while [ -z $custom_cert_pos ]; do
	    read -p "Full path to a CA-cert on your computer (e.g. /etc/apache2/ca/ca.crt): " \
		custom_cert_pos

	    if [ ! -f $custom_cert_pos ]; then
		custom_cert_pos=""
	    fi
	done
	cp $custom_cert_pos ${install_path}${ca_cert_base_path}/${ca_cert_path}/${ca_cert_name}

	# get and copy the CA's private key into Confusa's cert/key directory
	while [ -z $custom_key_pos ]; do
	    read -p "Full path to a CA-private key on your computer (e.g. /etc/apache2/ca/ca.key): " \
		custom_key_pos
	    if [ ! -f $custom_key_pos ]; then
		custom_key_pos=""
	    fi
	done
	cp $custom_key_pos ${install_path}${ca_cert_base_path}/${ca_key_path}/${ca_key_name}

    elif [ -z $ca_key_name ]; then
	echo "Error: The name of CA-key is not set in the configuration!"
    elif [ -z $ca_cert_name ]; then
	echo "Error: The name of the CA-cert is not set in the configuration!"
    fi

    echo ""
    echo "Trying to set the right permissions for the ca execution directory"
    res=0
    chown -R $custom_apache_user $ca_write_dir
    res=`expr $res + $?`
    touch ${ca_write_dir}/ca.db.index
    res=`expr $res + $?`
    touch ${ca_write_dir}/ca.db.index.attr
    res=`expr $res + $?`
    # Give apache read permissions on the private key
    chown -R $custom_apache_user ${install_path}${ca_cert_base_path}/${ca_key_path}/${ca_key_name}
    res=`expr $res + $?`
    chmod 400 ${install_path}${ca_cert_base_path}/${ca_key_path}/${ca_key_name}
    res=`expr $res + $?`

    if [ ! $res -eq 0 ]; then
	echo "Something went wrong when trying to assign the right permissions to the CA keys/files"
	echo "Please make yourself sure that the files in ${ca_write_dir} and"
	echo "${install_path}${ca_cert_base_path} have the right permissions!"
    fi
}

function perform_postinstallation_steps
{
	echo "
	*********************************************************************
	Configuration done, performing postinstall...
	(NB: You can always change the configuration by editing
	${custom_install_path}config/confusa_config.php

	Press any key to continue or wait 10 seconds
	*********************************************************************"
	read -n1 -t10 any_key

	# only populate the database if that has not already been done by dbconfig
	if [ ! -f $dbconfig_template ]; then
		if [ ! -f "/root/mysql_root.pw" ]; then
			TMPFILE="/root/mysql_root.pw"

			while [ -z $mysql_root_password ]; do
				echo "Please specify the MySQL root password, so the installer can"
				echo -n "bootstrap the MySQL-database: "
				stty -echo
				read mysql_root_password
				stty echo
				echo ""

				# Test if the user specified the correct password
				mysql -u'root' -p${mysql_root_password} -hlocalhost -e "use mysql"
				res=$?

				if [ $res -ne 0 ]; then
					mysql_root_password=""
				fi
			done

			echo $mysql_root_password > $TMPFILE
		fi

		echo "Wrote mysql_root password temporarily to /root/mysql_root.pw. "
		echo "If mysql-execution fails, please delete this file manually."
		sh create_database.sh --delete_user
		res=$?
		rm -f $TMPFILE

		if [ ! $res -eq 0 ]; then
			echo "Failed when populating the DB!"
			perror $res
			exit $res
		fi
	else
		echo "DB has already been populated by dbconfig-common, skipping..."
	fi

	# Guess the name of the apache/httpd user
	# We search for:
	# - apache
	# - http
	# If none is found, use www-data
	# Note that the [ ] is used to make ps aux NOT include the grep command itself
	apache_user=`ps aux | grep [a]pache | cut -d " " -f 1 | tail -n 1`
	if [ -z $apache_user ]; then
		apache_user=`ps aux | grep [h]ttpd | cut -d " " -f 1 | tail -n 1`
	fi
	if [ -z $apache_user ]; then
		apache_user="www-data"
	fi
	read -p "Name of the apache user [$apache_user]: " custom_apache_user

	if [ -z $custom_apache_user ]; then
		custom_apache_user=$apache_user
	fi


	# Find various paths from config, create requried directories
	# and set the access-rights so we can use them
	res=0
	if ! install_path=`_get_config_entry 'install_path'`; then
	    echo "could not find install_path in config $config!"
	fi

	if ! simplesaml_path=`_get_config_entry 'simplesaml_path'`; then
	    echo "could not find simplesaml_path in config $config!"
	fi
	if ! ca_mode=`_get_config_entry 'ca_mode'`; then
	    echo "could not find ca_mode in config $config!"
	fi
	if ! confusa_log=`_get_config_entry 'default_log'`; then
	    echo "could not find default_log in config $config!"
	fi
	if ! custom_css_path=`_get_config_entry 'custom_css'`; then
	    mkdir -p ${custom_css_path}
	    chown -R $custom_apache_user ${custom_css_path}
	    res=`expr $res + $?`
	    chmod 0755 ${custom_css_path}
	    res=`expr $res + $?`
	else
	    echo "could not find custom_css in config $config!"
	fi

	if ! custom_graphics_path=`_get_config_entry 'custom_logo'`; then
	    mkdir -p ${custom_graphics_path}
	    chown -R $custom_apache_user ${custom_graphics_path}
	    res=`expr $res + $?`
	    chmod 0755 ${custom_graphics_path}
	    res=`expr $res + $?`
	else
	    echo "could not find custom_logo in config $config!"
	fi

	if ! custom_template_path=`_get_config_entry 'custom_mail_tpl'`; then
	    mkdir -p ${custom_template_path}
	    chown -R $custom_apache_user ${custom_template_path}
	    res=`expr $res + $?`
	    chmod 0755 ${custom_template_path}
	    res=`expr $res + $?`
	else
	    echo "could not find custom_mail_tpl in config $config!"
	fi

	# bootstrap smarty directories
	smarty_templates_c=`grep "SMARTY_TEMPLATES_C" $constants | cut -d '=' -f 2 | cut -d "'" -f 2 | cut -d "'" -f 1`
	mkdir -p ${smarty_templates_c}
	chown -R $custom_apache_user ${smarty_templates_c}
	res=`expr $res + $?`

	smarty_cache=`grep "SMARTY_CACHE" $constants | cut -d '=' -f 2 | cut -d "'" -f 2 | cut -d "'" -f 1`
	mkdir -p ${smarty_cache}
	chown -R $custom_apache_user ${smarty_cache}
	res=`expr $res + $?`

	if [ ! $res -eq 0 ]; then
		echo "Failed in setting the right permissions for the installation path!"
		echo "Please ensure yourself that $custom_apache_user has write access to"
		echo "${install_path}www/css/custom"
		echo "${install_path}www/graphics/custom"
		echo "${smarty_templates_c}"
		echo "${smarty_cache}"
		exit $res
	fi


	# Manage database-crontab
	get_user_alternative "Do you want setup to install a crontab for cleaning and backing up the DB? (Y/n)" "Y"

	if [ $answer == "y" ] || [ $answer == "Y"; then
		write_cron_jobs ${install_path}
	fi

	# Set the right permissions on the Confusa log
	mkdir -p `dirname $confusa_log`
	res=$?
	touch $confusa_log
	res=`expr $res + $?`
	chown $custom_apache_user $confusa_log
	res=`expr $res + $?`

	if [ ! $res -eq 0 ]; then
		echo "Failed to set the right permissions on the confusa-log in $confusa_log"
		echo "Please make sure yourself that $custom_apache_user has write access to "
		echo "$confusa_log"
	fi

	# Setup the permissions for the cert-handling stuff
	if [ $ca_mode = "STANDALONE" ]; then
		postinstall_standalone $custom_apache_user $install_path
	fi

	echo "

	*********************************************************************
	Thanks for using the Confusa installer.

	Scripts you might want to run:
	bootstrap_nren : Connect a new NREN to Confusa
	bootstrap_idp  : Connect an identity provider to that NREN
	*********************************************************************"
} # end perform_postinstallation_steps


################################################################################
#
#				Script entry point
#
################################################################################

if [ ! `whoami` == "root" ]; then
	echo "Need to be root to run setup!"
	exit
fi

echo "
	*********************************************************************

			Welcome to the Confusa-setup

	This will guide you through the configuration. It would be wise to keep the
	INSTALL-documentation handy if this is the first time you perform the
	installation.

	Remember, you can always direct questions to confusa-dev@confusa.org and at
	our IRC-channel #confusa@freenode.

	********************************************************************
"
# Check if the config contains "valid_install" and ask the user if the
# configuration block should be skipped in that case
if [ -f $config ]; then
    # FIXME: this should be done a bit cleaner
    valid_install=`grep "valid_install" $config | cut -d ">" -f 2 | cut -d "," -f 1 | tr -d [:blank:]`

    if [ "$valid_install" = "true" ]; then
	get_user_alternative "Updated configuration found. Skip configuration section? (Y/n) :" "Y"

	# do not skip configuration, go ahead with full-blown config-edit
	if [[ $answer == "n" || $answer == "N" ]]; then
	    configure_confusa_settings
	else
	    copy_new_config_flags
	fi

	perform_postinstallation_steps
	else
	configure_confusa_settings
	perform_postinstallation_steps
    fi #end if valid_install

# Config template does not yet exist, copy it and walk the user through
# configuration
else
	cp $config_template $config
	configure_confusa_settings
	perform_postinstallation_steps
fi
