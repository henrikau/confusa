#!/bin/bash
# Not verified!
#
# Script for dumping the content of database to file
#
# Author: Henrik Austad <henrik.austad@uninett.no>

if [ ! `whoami` == "root" ]; then
    echo "Should be root"
fi
if [ ! $# -eq 1 ]; then
    echo "Errors in parameters, need exactly 1, $# given"
    usage
fi

if [ ! -f $1 ]; then
    echo "config-file does not exist"
    usage
fi
configfile=$1

# find database to do backup from
database=`grep "mysql_db" $configfile | cut -d '=' -f 2\
    | cut -d "'" -f 2`
if [ -z "$database" ]; then
    echo "Cannot find configured database in confusa_config. Aborting"
    exit
fi

# find backupdir
backupdir=`grep "mysql_backup_dir" $configfile | cut -d '=' -f 2\
    | cut -d "'" -f 2`"/"
if [ -z "$backupdir" ]; then
    echo "Need a backupdir. Please set mysql_backup_dir in confusa_config.php"
    echo "Aborting"
    exit
fi


# test for debian-sys-maint
file=/root/mysql_root.pw
if [ ! -f $file ]; then
    echo "$file not set. Aborting"
    exit
fi
pw=`cat /root/mysql_root.pw`
res=`mysqldump -uroot -p$pw $database`
if [ -z "$res" ]; then
    echo "dump of database failed for some reason."
    exit
fi
d=`date +%F_%T`
fname=$backupdir$database"_backup_$d"
echo $res > $fname

echo "Backup done"
