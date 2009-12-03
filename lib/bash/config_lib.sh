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

    # Need one and only *one* parameter
    if [ ! $# -eq 1 ]; then
	return 127
    fi

    # Test $1 for wildcards
    # TODO

    # Find the key
    res=`grep "$1'[^]]" ${config_dir}/confusa_config.php | cut -d '=' -f 2 | cut -d "'" -f 2`
    if [ "$res" == "" ]; then
		echo "did not find key $1" >&2
	return 1
    fi
    echo $res
    return 0
}
