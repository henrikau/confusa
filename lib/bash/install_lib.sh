#!/bin/bash
# install_lib
#
# This file contains functions used by the installer.
#
# The functions are 'generic' functions, that is, they do a particular
# task common in the installer. It should not be a task that affects the
# flow of the installer.



# get_user_alternative
#
# Call this function for simple yes/no questions with the questions as
# an argument
#
# Optional: Supply default value, must be yYnN
function get_user_alternative
{
    default=""
    if [ ! -z $2 ]; then
	case $2 in "y"|"Y"|"n"|"N") default=$2 ;; esac
    fi
    msg=$1
    answer=""
    retry=""
    while [ "$answer" == "" ]; do
	read -p "$msg $retry: " answer
	case $answer in
	    "y"|"Y"|"n"|"N")
		break ;;
	    *)
		if [ ! -z $default ] && [ "$answer" = "" ]; then
		    answer=${default}
		    break
		fi
		answer=""
		retry="(retry) "
		;;
	esac
    done
}

# replace_config_entry
#
# Take the supplied key and value and use the template to verify it's
# existence before setting it in the config.
#
# The function expects the config to be set globally (done at the start
# of the file), the same with the template.
#
# If you need to insert something directly, without adding quotes etc,
# use the _raw-function
function replace_config_entry
{
    # Replace entry in the configuration file with the new value
    sed s\|"'$1'[^]][ \t]*=>.*"\|"'$1'    => '$2',"\| < $working_template > $config
    cp $config $working_template
}
function replace_config_entry_raw
{
    # Replace entry in the configuration file with the new value
    sed s\|"'$1'[^]][ \t]*=>.*"\|"'$1'    => $2,"\| < $working_template > $config
    cp $config $working_template
}

# get_from_config_template
#
# This will return a valie from the config-template. If it is not found,
# an empty string is returned
#
# The function assumes that $working_template is initalized to the full
# path of the config-template.
function get_from_config_template
{
    res=""
    if [ ! -z $working_template ]; then
	res=`grep "'$1'" $working_template | cut -d '=' -f 2 | cut -d "'" -f 2`
    fi
}

function single_trailing_slash
{
    res=""
    if [ ! -z $1 ]; then
	res="`echo $1|sed 's/[\/]*$//g'`/"
    fi
    echo $res
}


function test_url
{
    echo `echo $1 | grep -E 'http[s]?://.*[\.+]'`
}

# test_email - test if the supplied string resembles an email-address
#
# It accepts emails on the form
# username@example.org
# user.name@example.org
# user@example.sub.domain.org
function test_email
{
    res=""
    if [ ! -z $1 ]; then
	res=`echo $1 | egrep "[a-zA-Z0-9-]+([._a-zA-Z0-9.-]+)*@[a-zA-Z0-9.-]+[\.[a-zA-Z0-9]*]*\.([a-zA-Z]{2,4})$"`
    fi
}

# if an older version of Confusa was installed before, maybe some new
# configuration flags (in confusa_config_template.php) were introduced
# in the meantime.
#
# This function checks for the presence of flags in the config-template
# that are not in the config file and copies them while preserving the
# existing configuration values.
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
	tmp_file="${prefix}/.flags_only"
	working_template_2="${prefix}/.template2"
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

function write_cron_jobs
{
	install_path=$1
	cron_file="/etc/cron.d/confusa"

	# Get absolute path to the config file
	conf_dir=`dirname $config`
	cnf_file=`cd $conf_dir; pwd`/`basename $config`

	if [ ! -d "/etc/cron.d" ]; then
		echo "Cron execution directory not found. Please make sure to have cron"
		echo "installed!"
		return
	fi

	cronline1="*/10  *    *    *  *       ${install_path}/programs/clean_db.sh ${cnf_file}"
	cronline2="0     */2  *    *  *   ${install_path}/programs/db_backup.sh ${cnf_file}"

	if [ -f $cron_file ]; then
		get_user_alternative "Cron file ${cron_file} already exists. Overwrite (y/n)?"

		if [ $answer == "n" ]; then
			return
		fi
	fi

	echo "$cronline1" > $cron_file
	res=$?
	echo "$cronline2" >> $cron_file
	res=`expr $res + $?`

	if [ $res -ne 0 ]; then
		echo -n "Writing Confusa's crontab to /etc/cron.d failed. Please make sure "
		echo -n "yourself that important scripts for cleaning and backing up the DB "
		echo "get executed regularly (e.g. by defining cronjobs using crontab -e)"
	fi

	echo -n "Wrote crontab to ${cron_file}. If you want to fine-tune the execution "
	echo "dates please edit the file."
}
