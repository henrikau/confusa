#!/bin/bash
# Assumes that the config_lib has been loaded.

username=`get_config_entry "mysql_username"`
pw=`get_config_entry "mysql_password"`
host=`get_config_entry "mysql_host"`
db=`get_config_entry "mysql_db"`
if [ "$pw" == "" ]; then
    pw=""
else
    pw="-p$pw"
fi
MYSQL="/usr/bin/mysql -N -h$host -u$username -D$db $pw -B"
function run_query ()
{
    if [ ! $# -eq 1 ]; then
	echo "Need query" >&2
	return -1
    fi
    $MYSQL -e"$1"
}