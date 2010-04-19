#!/bin/bash
# Assumes that the config_lib has been loaded.

# if the DB was configured by dbconfig-common, get the connection information
# from dbconfig-common's configuration file
if [ -f "/etc/confusa/confusa_config.inc.php" ]; then
	db_config_file="/etc/confusa/confusa_config.inc.php"

	username=`grep "\\$dbuser=" $db_config_file | cut -d '=' -f 2 \
		| cut -d "'" -f 2`
	pw=`grep "\\$dbpass=" $db_config_file | cut -d '=' -f 2 \
		| cut -d "'" -f 2`
	pw=-p'${pw}'
	host=`grep "\\$dbserver=" $db_config_file | cut -d '=' -f 2 \
		| cut -d "'" -f 2`
	db=`grep "\\$dbname=" $db_config_file | cut -d '=' -f 2 \
		| cut -d "'" -f 2`
else
    if [ -z ../lib/bash/config_lib.sh ]; then
	echo "Cannot find config-library. Aborting."
	exit 5
    fi
    if ! username=`get_config_entry "mysql_username"`; then
	echo "Could not retrieve database-username from config. Aborting" >&2
    fi

    if ! pw=`get_config_entry "mysql_password"`; then
	echo "Could not retrieve database-password from config. Assuming no password" >&2
	pw=""
    else
	pw="-p$pw"
    fi

    if ! host=`get_config_entry "mysql_host"`; then
	echo "Could not find database-hoset in config. Using localhost as default" >&2
	host="localhost"
    fi
    host="-h$host"

    if ! db=`get_config_entry "mysql_db"`; then
	echo "Could not find database-name in config. Aborting" >&2
	exit 127
    fi
fi

MYSQL="/usr/bin/mysql --skip-column-names ${host} -u'${username}' -D$db ${pw} -B"

function run_query ()
{
    if [ ! $# -eq 1 ]; then
	echo "Need query" >&2
	return -1
    fi
    $MYSQL -e"$1"
}
