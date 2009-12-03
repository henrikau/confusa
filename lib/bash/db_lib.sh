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
	host=`grep "\\$dbserver=" $db_config_file | cut -d '=' -f 2 \
		| cut -d "'" -f 2`
	db=`grep "\\$dbname=" $db_config_file | cut -d '=' -f 2 \
		| cut -d "'" -f 2`
else
	username=`get_config_entry "mysql_username"`
	pw=`get_config_entry "mysql_password"`
	host=`get_config_entry "mysql_host"`
	db=`get_config_entry "mysql_db"`
fi

if [ "$pw" == "" ]; then
    pw=""
else
    pw="-p$pw"
fi

MYSQL="/usr/bin/mysql --skip-column-names -h$host -u'${username}' -D$db ${pw}"

function run_query ()
{
    if [ ! $# -eq 1 ]; then
	echo "Need query" >&2
	return -1
    fi
    $MYSQL -e"$1"
}
