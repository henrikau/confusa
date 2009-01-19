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
if [ -f /etc/mysql/debian.cnf ]; then
    echo "Using debian-sys-maintainer config"
    MYSQL="/usr/bin/mysql --defaults-file=/etc/mysql/debian.cnf"
else 
    user="root"
    host="localhost"
    if [ -f /root/mysql_root.pw ]; then 
	pass="-p`cat /root/mysql_root.pw`"
    fi
    MYSQL="/usr/bin/mysql -u$user -h$host $pass"
fi
# use the database stated in the confusa_config.php. If this file is not
# present, the script will terminate
if [ ! -f "../config/confusa_config.php" ]; then
    echo "*need* the confusa_config.php file!"
    echo "Please configure this properly before you re-run this script"
    exit
fi
echo "Found ../config/confusa_config.php OK. Continuing"

# Check to se if the database itself is present in MySQL
# if not, create it
database=`grep "mysql_db" ../config/confusa_config.php | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
if [ ! -n $databaase ]; then
    echo "mysql-db not set in config-file!"
fi
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
$MYSQL -D$database < table_create.sql

# check to see if the the proper user with rights are in place
webuser=`grep "mysql_username" ../config/confusa_config.php | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
pw=`grep "mysql_password" ../config/confusa_config.php | cut -d '=' -f 2 \
    | cut -d "'" -f 2`
grants="INSERT, DELETE, UPDATE, USAGE"

# test to see if the user is already present. If not, add
user=`$MYSQL -Dmysql -e "SELECT user FROM user WHERE user='$webuser'"`
if [ -z "$user" ]; then
    echo "did not find user ($webuser) in database, creating"
    res=`$MYSQL -D$database -e "GRANT $grants on $database.* TO '$webuser'@'localhost' IDENTIFIED BY '$pw'"`
    echo "Added user to database."
else
    echo "Found user ($webuser)."
fi

echo "Confusa-setup complete"