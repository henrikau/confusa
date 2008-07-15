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

if [ -f /etc/mysql/debian.cnf ]; then
    echo "Using debian-sys-maintainer config"
    MYSQL="/usr/bin/mysql --defaults-file=/etc/mysql/debian.cnf"
else 
    if [ -f /root/mysql_root.pw ]; then 
	user="root"
	pass="`cat /root/mysql_root.pw`"
	host="localhost"
	MYSQL="/usr/bin/mysql -u$user -h$host -p$pass"
    else
	echo "Cannot run without password - create /root/mysql_root.pw first"
	exit
    fi
fi

# use the database stated in the confusa_config.php. If this file is not
# present, the script will terminate
if [ ! -f "../config/confusa_config.php" ]; then
    echo "*need* the confusa_config.php file!"
    echo "Please configure this properly before you re-run this script"
    exit
fi

# Check to se if the database itself is present in MySQL
# if not, create it
database=`grep "mysql_db" ../config/confusa_config.php | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
echo $database
if [ ! -n $databaase ]; then
    echo "mysql-db not set in config-file!"
fi
res=`$MYSQL -e "SHOW DATABASES like '$database'"`
if [ ! -n "$res" ]; then
    echo "Creating database $database";
    res=`$MYSQL -e "CREATE DATABASE $database"`
fi

# add tables
$MYSQL -D$database < table_create.sql

# check to see if the the proper user with rights are in place
webuser=`grep "mysql_username" ../config/confusa_config.php | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
pw=`grep "mysql_password" ../config/confusa_config.php | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
grants="INSERT, DELETE, UPDATE, USAGE"
# echo $MYSQL -D$database -e "GRANT $grants on $database.* TO '$webuser'@'localhost' IDENTIFIED BY '$pw'"
