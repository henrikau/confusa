#!/bin/sh
# Confusa auto-setup.
#
# GPL v3 applies

# Try to find the config directory. Depending on whether Confusa was
# downloaded from the Git repository or installed from the Debian
# package, that is in the Confusa directory or in /etc/confusa/config/
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

config_template=${prefix}"confusa_config_template.php"
working_template="/tmp/.confusa_wrk_template"
config=${prefix}"confusa_config.php"

# Call this function for simple yes/no questions with the questions as an argument
function get_user_alternative
{
	answer=""

	while [ -z $answer ]; do
		echo -n $1
		read answer
		case $answer in
			"y"|"n") break ;;
		esac

		answer=""
	done
}

function replace_config_entry
{
	# Replace entry in the configuration file with the new value
	sed s\|"'$1'[ \t]*=>.*"\|"'$1'    => '$2',"\| < $working_template > $config
	cp $config $working_template
}

function replace_interval_in_config
{
	LEGAL_VALUES=("SECOND" "MINUTE" "HOUR" "DAY" "WEEK" "MONTH" "YEAR")

	while [ -z $unit ] || [ -z $value ]; do
		echo "Please specify the format in the notation \$time \$unit, where unit can be one of"
		echo -n "(SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, YEAR) [$2 $3]: "
		read tmp_timeout

		if [ -z "$tmp_timeout" ]; then
			tmp_timeout=`echo "$2 $3"`
		fi

		# TODO: proper error handling (is value numeric etc.)
		tmp_value=`echo $tmp_timeout | cut -d " " -f 1`
		tmp_unit=`echo $tmp_timeout | cut -d " " -f 2`

		# Check if the value is a number
		if [ $tmp_value -eq $tmp_value 2> /dev/null ]; then
			value=$tmp_value
		else
			continue;
		fi

		for (( i=0;i<${#LEGAL_VALUES[@]};i++ )); do
			if [ $tmp_unit = ${LEGAL_VALUES[${i}]} ]; then
				unit=$tmp_unit
				break
			fi
		done
	done

	sed s\|"'$1'[ \t]*=>.*"\|"'$1'		=> array($value,'$unit'),"\| < $working_template > $config
	cp $config $working_template
	unit=""
	value=""
}

function configure_confusa_settings
{
	cp $config_template $working_template

	echo ""
	echo "*********************************************************************"
	echo " We will walk you through the configuration of the most important    "
	echo " Confusa settings. The idea is to get a working basic Confusa        "
	echo " instance. You can configure Confusa in a more fine-grained way by   "
	echo " editing confusa_config.php in Confusa's config directory.           "
	echo "*********************************************************************"
	echo ""
	echo "Press any key to continue to continue or wait 20 seconds            "
	read -n1 -t20 any_key

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
	# upload
	# download
	# approve
	# post_login_page		- Advanced options
	# custom_css			- advanced
	# custom_logo			- advanced
	# capi_apply_endpoint	- not supposed to change, advanced
	# capi_auth_endpoint	- not supposed to change, advanced
	# capi_collect_endpoint	- not supposed to change, advanced
	# capi_listing_endpoint	- ditto
	# capi_revoke_endpoint	- ditto
	# capi_escience_id		- ditto
	# capi_personal_id		- ditto
	# capi_root_cert		- ditto
	# capi_crl				- ditto
	# capi_test				- should be set to false in the template
	# ca_cert_base_path
	# ca_cert_path
	# ca_cert_name
	# ca_key_path
	# ca_key_name
	# ca_conf_name
	# ca_crl_name			- if there is a custom CA configuration,
	#						- it is probably not in place at the time of installation!
	# script_check_ssl		- should be set to true in the template
	# csr_var				- advanced, probably not changed very often
	# auth_var				- advanced, probably not changed very often
	# auth_length			- see above
	# loglevel_min
	# syslog_min			- when you dig into logfiles, you can also change config files
	# auth_bypass			- not supposed to change, set false in template
	# language.availabe		- set to something reasonable in template
	# language.default		- set to 'en' in template, if somebody wants to change => config file
	#
	###############################################################################

	# Confusa's operation mode
	###############################################################################
	echo ""
	echo ""
	echo "Confusa can operate in two modes. The first one is standalone, "
	echo "in which Confusa will use its own installed CA to sign certificates for "
	echo "the user, the other one is online, in which a hooked up remote CA will "
	echo "be used for signing certificate request, revocation etc. Currently this "
	echo "Online-CA is Comodo. In which mode do you wish to operate Confusa?"
	echo ""
	select mode in online standalone; do
		case $mode in
			"standalone") ca_mode="CA_STANDALONE" ;;
			"online") ca_mode="CA_ONLINE" ;;
			*) continue ;;
		esac

		# need the mode without exclamation marks, because it is an enumeration
		sed s\|"'ca_mode'[ \t]*=>.*"\|"'ca_mode'    => $ca_mode,"\| < $working_template > $config
		cp $config $working_template
		break
	done

	echo ""

	# Autoconfigure:
	# Set some config flags to feasible values
	###############################################################################
	if [ $mode = "online" ]; then
		sed s\|"'capi_test'[ \t]*=>.*"\|"'capi_test'    => false,"\| < $working_template > $config
		cp $config $working_template
	elif [ $mode = "standalone" ]; then
		# hardcode for the sake of simplicity
		# c'mon don't be picky :)
		replace_config_entry "ca_cert_name" "servercert.pem"
		replace_config_entry "ca_key_name"  "serverkey.pem"
	fi

	sed s\|"'debug'[ \t]*=>.*"\|"'debug'    => false,"\| < $working_template > $config
	cp $config $working_template
	sed s\|"'maint'[ \t]*=>.*"\|"'maint'    => false,"\| < $working_template > $config
	cp $config $working_template
	sed s\|"'script_check_ssl'[ \t]*=>.*"\|"'script_check_ssl'    => true,"\| < $working_template > $config
	cp $config $working_template
	sed s\|"'auth_bypass'[ \t]*=>.*"\|"'auth_bypass'    => false,"\| < $working_template > $config
	cp $config $working_template
	replace_config_entry "language.default" "en"
	replace_config_entry "default_log" "/var/log/confusa.log"

	cp $config $working_template


	# Guess the installation path of Confusa to use it as default (assuming bash)
	#############################################################################
	script_dir=`pwd`
	install_path=`echo | awk -v sdir=$script_dir '{sub("init", "", sdir); print sdir}'`

	echo -n "Confusa install path: [$install_path]: "
	read custom_install_path

	# don't be tricked by erroneous input
	while [ ! -d "$custom_install_path" ]; do
		if [ -z $custom_install_path ]; then
			custom_install_path=$install_path
		else
			echo -n "Confusa install path must be a directory [$install_path]: "
			read custom_install_path
		fi
	done

	has_trailing_slash=`echo $custom_install_path | grep "/$"`
	if [ "$has_trailing_slash" = "" ]; then
		custom_install_path=${custom_install_path}/
	fi

	# The path to the program for the key generation script
	replace_config_entry "install_path" $custom_install_path
	echo ""

	# Configure the server url
	###############################################################################
	while [ 1 == 1 ]; do
		while [ -z $server_url ]; do
			echo "Please enter the URL of the server with Confusa installed: "
			echo -n "[e.g. https://beta.confusa.org]: "
			read server_url

			# sloppily check if that thingie looks remotely like a URL
			server_url=`echo $server_url | grep https://.*[\.+]`
		done

		# Remove any trailing slash
		server_url=${server_url%/}

		echo ""
		while [ -z $server_path ]; do
			echo -n "Please enter the path to Confusa on your server [e.g. /confusa/]: "
			read server_path

			# check if the thingie has a leading slash
			has_leading_slash=`echo $server_path | grep "^/"`
			if [ -z $has_leading_slash ]; then
				server_path=/${server_path}
			fi

			has_trailing_slash=`echo $server_path | grep "/$"`
			if [ -z $has_trailing_slash ]; then
				server_path=${server_path}/
			fi
		done

		echo ""
		get_user_alternative "The full path to Confusa is ${server_url}${server_path}? (y/n)"

		if [ $answer = "y" ]; then
			break
		else
			server_url=""
			server_path=""
			echo ""
			echo ""
			continue
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
	echo -n "Please enter the path to simplesamlphp [$simplesaml_path]: "
	read custom_simplesaml_path

	while [ ! -d "$custom_simplesaml_path" ]; do
		if [ -z $custom_simplesaml_path ]; then
			custom_simplesaml_path=$simplesaml_path
		else
			echo -n "Need a directory for the simplesaml path [$simplesaml_path]: "
			read custom_simplesaml_path
		fi
	done

	has_trailing_slash=`echo $custom_simplesaml_path | grep "/$"`
	if [ "$has_trailing_slash" = "" ]; then
		custom_simplesaml_path=${custom_simplesaml_path}/
	fi

	replace_config_entry "simplesaml_path" $custom_simplesaml_path
	echo ""

	## Configure the path to smarty
	################################################################################
	SMARTY_GUESS=("/usr/share/php/smarty/" "/usr/share/php/smarty/lib/")

	for (( i=0;i<${#SMARTY_GUESS[@]};i++ )); do
		if [ -f ${SMARTY_GUESS[${i}]}/Smarty.class.php ]; then
			smarty_path=${SMARTY_GUESS[${i}]}
			break
		fi
	done

	if [ -z $smarty_path ]; then
		smarty_path=`grep "'smarty_path'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`
	fi
	echo -n "Please enter the path to the PHP template engine smarty [$smarty_path]: "
	read custom_smarty_path

	while [ ! -d "$custom_smarty_path" ]; do
		if [ -z $custom_smarty_path ]; then
			custom_smarty_path=$smarty_path
		else
			echo -n "Need a directory for the smarty path! [$smarty_path]: "
			read custom_smarty_path
		fi
	done

	has_trailing_slash=`echo $custom_smarty_path | grep "/$"`
	if [ "$has_trailing_slash" = "" ]; then
		custom_smarty_path=${custom_smarty_path}/
	fi

	replace_config_entry "smarty_path" $custom_smarty_path
	echo ""

	# ONLINE: generate a password with which the login password will be protected
	# if pwgen is not installed, then ask for a password
	###############################################################################
	if [ $mode == "online" ]; then
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
	###############################################################################
	key_length=`grep "'key_length'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo "Specify the minimum key length in bits for Confusa-issued certificates "
	echo -n "(It is recommended to have a key length >= 1024) [$key_length]: "
	read custom_key_length

	# basic check on the key length
	while [ 1 -eq 1 ]; do
		if [ -z $custom_key_length ]; then
			custom_key_length=$key_length
			break
		else
			case $custom_key_length in
				512|1024|2048|4096) break ;;
			esac

			echo -n "Key length must be one of 512, 1024, 2048 and 4096 [$key_length]: "
			read custom_key_length
		fi
	done

	replace_config_entry "key_length" $custom_key_length
	echo ""

	# Configure where to report errors
	###############################################################################
	if [ $mode = "standalone" ]; then
		while [ -z $error_addr ]; do
			echo -n "Where should the users report error in the script to: "
			read error_addr

			# Sloppily check if that thingie remotely ressembles a mail address
			error_addr=`echo $error_addr |  egrep "[a-zA-Z0-9-]+([._a-zA-Z0-9.-]+)*@[a-zA-Z0-9.-]+\.([a-zA-Z]{2,4})$"`
		done

		replace_config_entry "error_addr" $error_addr
	fi

	echo ""

	# Configure the mysql username
	##############################################################################
	mysql_username=`grep "'mysql_username'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo -n "The user-name for accessing the MySQL-DB [$mysql_username]: "
	read custom_mysql_username

	if [ -z $custom_mysql_username ]; then
		custom_mysql_username=$mysql_username
	fi

		replace_config_entry "mysql_username" $custom_mysql_username
		echo ""

	# Configure the mysql password
	###############################################################################
	have_pwgen=`which pwgen`

	if [ "$?" -eq "0"  ]; then
		echo "Generating mysql password with pwgen..."
		mysql_password=`pwgen -1 -n 12 -s`
	fi

	if [ -z $have_pwgen ] || [ ! $? -eq 0 ]; then
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
	################################################################################
	mysql_host=`grep "'mysql_host'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo -n "The host on which mysql is to run [$mysql_host]: "
	read custom_mysql_host

	if [ -z $custom_mysql_host ]; then
		custom_mysql_host=$mysql_host
	fi

	replace_config_entry "mysql_host" $custom_mysql_host
	echo ""

	# Configure the mysql-DB-name
	###############################################################################
	mysql_db=`grep "'mysql_db'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo -n "Enter DB (name) which should be used for Confusa [$mysql_db]: "
	read custom_mysql_db

	if [ -z $custom_mysql_db ]; then
		custom_mysql_db=$mysql_db
	fi

	replace_config_entry "mysql_db" $custom_mysql_db
	echo ""

	# Configure the mysql-backup dir
	###############################################################################
	mysql_backup_dir=`grep "'mysql_backup_dir'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo -n "Specify the directory in which backups of the MySQL-DB are stored [$mysql_backup_dir]: "
	read custom_mysql_backup_dir

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
	system_name=`grep "'system_name'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo "Enter the name of the system. This name will appear in titles in "
	echo -n "the browser [$system_name]: "
	read custom_system_name

	if [ -z $custom_system_name ]; then
		custom_system_name=$system_name
	fi

	replace_config_entry "system_name" $custom_system_name
	echo ""

	# Configure the sys_from address
	###############################################################################

	while [ -z $custom_sys_from_address ]; do
		echo -n "Configure the address that shows up in mails from the system: "
		read custom_sys_from_address

		# Sloppily check if that thingie remotely ressembles a mail address
		custom_sys_from_address=`echo $custom_sys_from_address | egrep "[a-zA-Z0-9-]+([._a-zA-Z0-9.-]+)*@[a-zA-Z0-9.-]+\.([a-zA-Z]{2,4})$"`
	done

	replace_config_entry "sys_from_address" $custom_sys_from_address
	echo ""

	# Configure the cert-default-timeout
	##############################################################################
	if [ $mode = "standalone" ]; then
		cert_default_timeout_value=`grep "'cert_default_timeout'" $working_template | cut -d '=' -f 2 \
				| cut -d '(' -f 2 | cut -d ',' -f 1`
		cert_default_timeout_unit=`grep "'cert_default_timeout'" $working_template | cut -d '=' -f 2 \
				| cut -d '(' -f 2 | cut -d ',' -f 2 | cut -d ')' -f 1 | cut -d "'" -f 2`

		echo "Specify the timeout for certificates, i.e. the interval within which "
		echo "they will be kept available for download to the user. "
		echo ""
		replace_interval_in_config "cert_default_timeout" $cert_default_timeout_value $cert_default_timeout_unit
		echo ""
	fi

	# Configure the CSR-default-timeout
	################################################################################
	csr_default_timeout_value=`grep "'csr_default_timeout'" $working_template | cut -d '=' -f 2 \
				| cut -d '(' -f 2 | cut -d ',' -f 1`
	csr_default_timeout_unit=`grep "'csr_default_timeout'" $working_template | cut -d '=' -f 2 \
			| cut -d '(' -f 2 | cut -d ',' -f 2 | cut -d ')' -f 1 | cut -d "'" -f 2`

	echo "Specify the timeout for CSRs, i.e. the interval within which the user "
	echo "will be able to authorize and view them. "
	echo ""
	replace_interval_in_config "csr_default_timeout" $csr_default_timeout_value $csr_default_timeout_unit
	echo ""

	# Configure the protected session timeout
	################################################################################
	protected_session_timeout=`grep "'protected_session_timeout'" $working_template | cut -d '=' -f 2 \
			| cut -d "'" -f 2`

	echo "The protected session timeout default value is: $custom_protected_session_timeout"

	custom_protected_session_timeout=""

	while [ 1 == 1 ]; do
		echo "How long should the session allow the user to perform \"sensitive\" actions "
		echo -n "(in minutes) [$protected_session_timeout]: "
		read custom_protected_session_timeout

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

	# Set valid install to true
	################################################################################
	sed s\|"'valid_install'[ \t]*=>.*"\|"'valid_install'    => true,"\| < $working_template > $config
	rm $working_template

	################################################################################
	## Configuration section done ##################################################
	################################################################################
}

# if an older version of Confusa was installed before, maybe some new configuration
# flags (in confusa_config_template.php) were introduced in the meantime.
# This function checks for the presence of flags in the config-template that
# are not in the config file and copies them while preserving the existing
# configuration values.
#
# Do the following:
#	1 Copy config template to working template
# 	2 Get all the configuration flags in the config template
#	3 For each flag:
#		- lookup if flag is in the config file, if it is assign value from config
#		  file to working template
#	4 Copy working template to config file
#
function copy_new_config_flags
{
	echo "Copying new config flags from the template, removing deprecated ones"
	cp $config_template $working_template
	tmp_file="../config/.flags_only"
	working_template_2="../config/.template2"
	# first remove comment lines, then find the configuration flags
	cat $config_template | egrep -v "^[[:space:]]*(/)?(\\*)" | egrep "=>" | cut -d '=' -f 1 > $tmp_file

	while read line
	do
		flag=`echo $line`
		# Remove trailing whitespace characters (otherwise grep will be confused later)
		flag="${flag%"${flag##*[![:space:]]}"}"
		# Lookup the value of the config flag in the regular config file
		config_line=`cat $config | egrep -v "^[[:space:]]*(/)?(\\*)" | egrep "${flag}[[:space:]]*=>.*"`

		if [ -n "$config_line" ]; then
			value=`echo $config_line | cut -d '=' -f 2 | cut -d ">" -f 2`
			# now remove the trailing ","
			value="${value%","}"
			sed s\|"$flag[ \t]*=>.*"\|"$flag    => $value,"\| < $working_template > $working_template_2
			mv $working_template_2 $working_template
		fi
	done < $tmp_file

	rm $tmp_file
	sed s\|"'$1'[ \t]*=>.*"\|"'valid_install'    => true,"\| < $working_template > $config
}

# configure the directories and permissions for the installed CA
# Offer the possibility to copy a cert/private key to these locations
function postinstall_standalone
{
		custom_apache_user=$1
		install_path=$2

		ca_cert_base_path=`grep "'ca_cert_base_path'" $config | cut -d '=' -f 2 | cut -d "'" -f 2`
		ca_cert_path=`grep "'ca_cert_path'" $config | cut -d '=' -f 2 | cut -d "'" -f 2`
		ca_crl_name=`grep "'ca_crl_name'" $config | cut -d '=' -f 2 | cut -d "'" -f 2`
		ca_cert_name=`grep "'ca_cert_name'" $config | cut -d '=' -f 2 | cut -d "'" -f 2`
		ca_key_path=`grep "'ca_key_path'" $config | cut -d '=' -f 2 | cut -d "'" -f 2`
		ca_key_name=`grep "'ca_key_name'" $config | cut -d '=' -f 2 | cut -d "'" -f 2`

		mkdir -p ${install_path}${ca_cert_base_path}/${ca_cert_path}
		mkdir -p ${install_path}${ca_cert_base_path}/${ca_key_path}
		mkdir -p ${install_path}www/ca

		if [ ! $? -eq 0 ]; then
			echo "Error creating the directory from which the custom certificates will be available"
			echo "(Tried to create ${install_path}www/ca)"
		fi

		get_user_alternative "Do you want to copy a certificate/key pair for signing from your filesystem to Confusa (y/n)?"

		if [[ $answer = "y" && -n $ca_key_name && -n $ca_cert_name ]]; then
			while [ -z $custom_cert_pos ]; do
				echo -n "Full path to a CA-cert on your computer (e.g. /etc/apache2/ca/ca.crt): "
				read custom_cert_pos

				if [ ! -f $custom_cert_pos ]; then
					custom_cert_pos=""
				fi
			done

			cp $custom_cert_pos ${install_path}${ca_cert_base_path}/${ca_cert_path}/${ca_cert_name}
			cp $custom_cert_pos ${install_path}www/ca/servercert.pem

			while [ -z $custom_key_pos ]; do
				echo -n "Full path to a CA-private key on your computer (e.g. /etc/apache2/ca/ca.key): "
				read custom_key_pos

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


		touch ${install_path}${ca_cert_base_path}/ca.db.index
		res=$?
		chown $custom_apache_user ${install_path}${ca_cert_base_path}/ca.db.index
		res=`expr $res + $?`
		touch ${install_path}${ca_cert_base_path}/ca.db.index.attr
		res=`expr $res + $?`
		chown $custom_apache_user ${install_path}${ca_cert_base_path}/ca.db.index.attr
		res=`expr $res + $?`
		touch ${install_path}${ca_cert_base_path}/${ca_crl_name}
		res=`expr $res + $?`
		chown $custom_apache_user ${install_path}${ca_cert_base_path}/${ca_crl_name}
		res=`expr $res + $?`
		# Create the serial number
		cert_prefix=`echo ${ca_cert_name} | cut -d '.' -f 1`
		res=`expr $res + $?`
		touch ${install_path}${ca_cert_base_path}/${ca_cert_path}/${cert_prefix}.srl
		res=`expr $res + $?`
		chown $custom_apache_user ${install_path}${ca_cert_base_path}/${ca_cert_path}/${cert_prefix}.srl
		res=`expr $res + $?`

		if [ ! $res -eq 0 ]; then
			echo "Something went wrong when trying to assign the right permissions to the CA keys/files"
			echo "Please make yourself sure that the files in ${install_path}${ca_cert_base_path} have "
			echo "the right permissions!"
		fi
}

function perform_postinstallation_steps
{
	echo ""
	echo "*********************************************************************"
	echo "Configuration done, performing postinstall..."
	echo "(NB: You can always change the configuration by editing "
	echo "${custom_install_path}config/confusa_config.php"
	echo ""
	echo "Press any key to continue or wait 20 seconds                         "
	echo "*********************************************************************"
	echo ""
	read -n1 -t10 any_key

	cd ../init/
	sh create_database.sh --delete_user
	res=$?

	if [ ! $res -eq 0 ]; then
		echo "Failed populating the DB!"
		perror $res
		exit $res
	fi

	echo ""
	echo ""
	cd `dirname $0`

	install_path=`grep "'install_path'" $config | cut -d "=" -f 2 | cut -d "'" -f 2`
	simplesaml_path=`grep "'simplesaml_path'" $config | cut -d "=" -f 2 | cut -d "'" -f 2`
	ca_mode=`grep "'ca_mode'" $config | cut -d "=" -f 2 | cut -d "_" -f 2 | cut -d "," -f 1`
	confusa_log=`grep "'default_log'" $config | cut -d "=" -f 2 | cut -d "'" -f 2`
	custom_css_path=`grep "'custom_css'" $config | cut -d "=" -f 2 | cut -d "'" -f 2`
	custom_graphics_path=`grep "'custom_logo'" $config | cut -d "=" -f 2 | cut -d "'" -f 2`

	# Link the necessary AuthProc filters
	ln -s -f ${install_path}include/CharacterMap.php ${simplesaml_path}modules/core/lib/Auth/Process/CharacterMap.php
	ln -s -f ${install_path}include/NRENMap.php ${simplesaml_path}modules/core/lib/Auth/Process/NRENMap.php

	# Get the permissions right
	# Guess the name of the apache/httpd user
	# Note that the [ ] is used to make ps aux NOT include the grep command itself
	apache_user=`ps aux | grep [a]pache | cut -d " " -f 1 | tail -n 1`

	if [ -z $apache_user ]; then
		apache_user=`ps aux | grep [h]ttpd | cut -d " " -f 1 | tail -n 1`
	fi

	if [ -z $apache_user ]; then
		apache_user="www-data"
	fi

	echo -n "Name of the apache user [$apache_user]: "
	read custom_apache_user

	if [ -z $custom_apache_user ]; then
		custom_apache_user=$apache_user
	fi

	mkdir -p ${custom_css_path}
	chown -R $custom_apache_user ${custom_css_path}
	res=$?
	mkdir -p ${custom_graphics_path}
	chown -R $custom_apache_user ${custom_graphics_path}
	res=`expr $res + $?`
	chmod 0755 ${custom_css_path}
	res=`expr $res + $?`
	chmod 0755 ${custom_graphics_path}
	res=`expr $res + $?`

	if [ ! $res -eq 0 ]; then
		echo "Failed in setting the right permissions for the installation path!"
		echo "Please ensure yourself that $custom_apache_user has write access to"
		echo "${install_path}www/css/custom"
		echo "${install_path}www/graphics/custom"
		exit $res
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
	fi # standalone handling


	echo ""
	echo "Do you want the Confusa setup to install simplesamlphp metadata (y/n)?"
	get_user_alternative "(If you have modified the metadata before, changes will be overwritten): "

	if [ $answer = "y" ]; then
		echo "Writing metadata to ${custom_simplesaml_path}/metadata/saml20-sp-hosted.php"
		cat > ${simplesaml_path}/metadata/saml20-sp-hosted.php <<EOF
<?php
	\$metadata = array(
		'__DYNAMIC:1__' => array(
				'host'  => '__DEFAULT__',
				'authproc' => array(
					60 => 'core:NRENMap',
					61 => 'core:CharacterMap'
				 ),
				'ForceAuthn' => true,
		),
	);
?>
EOF
	fi

	# TODO: add in that order, once NREN bootstrapping exists:
	#		bootstrapNREN
	#		bootstrapAdmin
	#
	#		if ca_mode = ONLINE:
	#			bootstrapAccount

	echo ""
	echo ""
	echo "*********************************************************************"
	echo "Thanks for using the Confusa installer. Please find further notes on "
	echo "Confusa's configuration in ${install_path}INSTALL"
	echo "*********************************************************************"
	echo ""
	echo ""
}

################################################################################
##### Script entry point
################################################################################

if [ ! `whoami` == "root" ]; then
	echo "Need to be root to run setup!"
	exit
fi

# execute the script from it's base directory. This makes handling of paths
# etc. much easier
cd `dirname $0`

echo ""
echo "*********************************************************************"
echo "Welcome to the Confusa setup. I will ask you a few questions and"
echo "        setup Confusa according to your answers!             "
echo "********************************************************************"
echo ""

# Check if the config contains "valid_install" and ask the user if the
# configuration block should be skipped in that case
if [ -f $config ]; then
	valid_install=`grep "valid_install" $config | cut -d ">" -f 2 | cut -d "," -f 1 | tr -d [:blank:]`

	if [ "$valid_install" = "true" ]; then
		get_user_alternative "Updated configuration found. Skip configuration section? (y/n) :"

		if [ $answer = "n" ]; then
			configure_confusa_settings
		else
			copy_new_config_flags
		fi

		perform_postinstallation_steps
	else
		configure_confusa_settings
		perform_postinstallation_steps
	fi
# Config template does not yet exist, copy it and walk the user through configuration
else
	cp $config_template $config
	configure_confusa_settings
	perform_postinstallation_steps
fi
