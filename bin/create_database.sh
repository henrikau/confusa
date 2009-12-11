#!/bin/bash
#
# Henrik Austad, July 2008, Uninett Sigma
#	henrik@austad.us
#
# Script for setting up the database with tables and user
# It will need root-user and root-access.
# As we optimize for debian-platform (sorry red-hat guys! :-), we use
# the debian-sys-maint hack to do this.
#
# If you do not use a debian-platform, make sure that you have a file
# named mysql_root.pw in /root, which contains the root-pw for the
# database (only one line, only password)
#
#

if [ ! `whoami` == "root" ]; then
    echo "Need to be root to run this"
    exit
fi

MYSQL_ROOT="/usr/bin/mysql -uroot -h localhost $root_pw"
if [ -f /root/mysql_root.pw ]; then
    root_pw="-p`cat /root/mysql_root.pw`"
else
    echo "Did not find /root/mysql_root.pw. If the root-account is password-protected, this step will fail"
fi
host="-hlocalhost"
user="-u'root'"
MYSQL="/usr/bin/mysql $user $host $root_pw"

# use the database stated in confusa_config.php or in dbconfig-common. If this file is not
# present, the script will terminate
dbconfig_file="/etc/confusa/confusa_config.inc.php"

if [ -f $dbconfig_file ]; then
	echo "Database configured by dbconfig_common. Skipping creation"
	exit 1
else
	if [ -f "../config/confusa_config.php" ]; then
		confusa_config="../config/confusa_config.php"
	elif [ -f "/etc/confusa/confusa_config.php" ]; then
		confusa_config="/etc/confusa/confusa_config.php"
	else
		echo "*need* the confusa_config.php file!"
		echo "Please configure this properly before you re-run this script"
		exit
	fi

	echo "Found $confusa_config OK. Continuing"

	database=`grep "mysql_db'[^]]" $confusa_config | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
	if [ -z $database ]; then
		echo "mysql-db not set in config-file!"
		echo "Please set this value and try again"
		exit
	fi

	webuser=`grep "mysql_username'[^]]" $confusa_config | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
	pw=`grep "mysql_password'[^]]" $confusa_config | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
	webhost=`grep "mysql_host'[^]]" $confusa_config | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
fi

# Check to se if the database itself is present in MySQL
# if not, create it
echo "Found configured database ($database) in config-file"
res=`$MYSQL -e "SHOW DATABASES like '$database'"`
if [ ! -n "$res" ]; then
    echo "Database $database not found. Creating..."
    res=`$MYSQL -e "CREATE DATABASE $database"`
else
    echo "Database ($database) exists, skipping creation"
fi

# add tables
echo "Creating tables in the database. Existing databases will be reset according to table_create.sql"
$MYSQL -D$database < ../init/table_create.sql
res=$?

if [ $res -ne 0 ]; then
    perror $res
    echo "Errors were encountered during the database install"
    echo "Make sure you have enough privileges to write to the database, and that "
    echo "the file table_create.sql has not been corrupted."
    echo ""
    echo "You might want to delete the entire database and try again.."
    exit
fi

# check to see if the the proper user with rights are in place
grants="SELECT, INSERT, DELETE, UPDATE, USAGE"

# test to see if the user is already present. If not, add
user=`$MYSQL -Dmysql -e "SELECT user FROM user WHERE user='$webuser' AND host='$webhost'"`

# if the script is called with --delete_user option, first get rid of the user
# this can make sense to make sure that the password defined in config is
# updated for the DB-user (otherwise the password stays the same as before,
# even if it has changed in the config)
if  [ -n "$user" ] && [ $# -eq 1 ] && [ $1 = "--delete_user" ]; then
    echo "Dropping user"
    `$MYSQL -Dmysql -e "DROP user '$webuser'@'$webhost'"`
    user=""
fi

if [ -z "$user" ]; then
    echo "did not find user ($webuser@$webhost) in database, creating"
    create_u="CREATE USER '$webuser'@'$webhost' IDENTIFIED BY '$pw'";
    `$MYSQL -D$database -e"$create_u"`
    res=$?
    if [ ! $res -eq 0 ]; then
        perror $res
        echo "Trouble adding user, aborting..."
        exit $res
    fi

    query="GRANT $grants on $database.* TO '$webuser'@'$webhost' IDENTIFIED BY '$pw'"
    `$MYSQL -D$database -e"$query"`
    res=$?
    if [ $res -eq 0 ]; then
	echo "Added user to database."
    else
	perror $res
	echo "Trouble adding user, aborting..."
	exit $res
    fi

else
    echo "Found user ($webuser@$webhost)."
fi

echo "Confusa-setup complete, adding views"
$MYSQL -D$database  < ../init/views_create.sql
echo "Views created. Database bootstrap complete."
echo ""
