#!/bin/bash
# Updates the DB schema of an already-defined Confusa database to match the
# schema of the deployed version.
#
# This helps the Confusa user to avoid data loss during an upgrade from an
# older version of Confusa, since which possibly the DB schema changed
#

# Perform the necessary changes on the DB schema to get it to the latest
# version.
# Parameter: The current version of the DB schema in the DB
function update
{
	case $res in
		# no op
		0) echo "Updating from 0" ;;
	esac
}

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
if [ -z $database ]; then
    echo "mysql-db not set in config-file!"
    echo "Please set this value and try again"
    exit
fi

echo "Getting the current schema from the database"
res=`$MYSQL -e "USE $database; SELECT version FROM schema_version"`

if [ $? -ne 0 ]; then
	perror $res
	echo "Could not get the current DB schema version."
	echo "Either your DB is uninitialized or corrupted."
	echo "If this is a fresh installation, did you run create_database.sh?"
	exit
fi

VERSION=`cat ../VERSION | grep DB_SCHEMA_VERSION | cut -d '=' -f 2`

if [ -z "$res" ]; then
	echo "Inserting current schema version $VERSION into the DB"
	res=`$MYSQL -e "USE $database; INSERT INTO schema_version(version) VALUES \
		($VERSION)"`

	if [ $? -ne 0 ]; then
		perror $res
		echo "Could not store the new database schema version in the DB"
		echo "Does root have INSERT permissions on the schema_version table?"
		exit
	fi
else
	db_version=`echo $res | cut -d ' ' -f 2`
	echo ""
	echo -e "Version of the release:\t$VERSION"
	echo -e "Version (in the DB):\t$db_version"
	echo ""
	if [ $VERSION -eq $db_version ]; then
		echo "DB-schema version in database and in VERSION file match."
		echo "No DB-schema update necessary."
		exit
	fi

	update $db_version
fi
