#!/bin/bash


# get_config_entry
#
# Find an entry in the config-file and return as a string.
#
# @configKey : the one and only parameter, the key to search for in the db.
# @return : 0 on success 1 on failure
#
# Usage:
#	- include the shell_lib.sh in your script
#		(. ./shell_script.sh)
#	- call the function with an argument and store the output to stdout in a variable
#		var=`get_config_entry "whatever"`
#		if [ ! $? -eq 0 ]; then
#		    echo "error"
#		else
#		    echo $var
#		fi
function get_config_entry ()
{
	if [ -f "../config/confusa_config.php" ]; then
		config_dir="../config"
	elif [ -f "/etc/confusa/confusa_config.php" ]; then
		config_dir="/etc/confusa"
	else
		echo "Confusa config file not found! Looked in"
		echo "../config/confusa_config.php and in"
		echo "/etc/confusa/confusa_config.php. Please create a config"
		echo "file, e.g. from the template or using the Installer before"
		echo "invoking this bootstrap script!"
		exit 64
	fi
	config=${config_dir}/confusa_config.php
	export $config
	res=_get_config_entry $1
	return $?
}

function _get_config_entry
{
    # Need one and only *one* parameter
    if [ ! $# -eq 1 ]; then
	return 127
    fi

    # Test $1 for wildcards
    # TODO

    # Find the key
    res=`grep "$1'[^]]" ${config} | grep ">" | cut -d '=' -f 2 | cut -d "'" -f 2`
    if [ "$res" == "" ]; then
	echo "did not find key $1" >&2
	return 1
    fi
    echo $res
    return 0
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
	res=`grep "'$1'" $working_template | grep ">" | cut -d '=' -f 2 | cut -d "'" -f 2`
    fi
}
