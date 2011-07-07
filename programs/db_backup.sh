#!/bin/bash
# Script for dumping the content of database to file
#
# Author: Henrik Austad <henrik.austad@uninett.no>

if [ ! `whoami` == "root" ]; then
    echo "Should be root"
    exit
fi

# set root to current dir
base=`dirname $0`
pushd $base >/dev/null

# Include libraries
if [ -z ../lib/bash/config_lib.sh ]; then
    echo "Cannot find config-library. Aborting."
    exit 127
fi
. ../lib/bash/config_lib.sh

# find database to do backup from
if ! database=`get_config_entry "mysql_db"`; then
    echo "Could not retreive name of database from config, aborting" > /dev/stderr
    exit
fi

# find backupdir
if ! backupdir=`get_config_entry "mysql_backup_dir"`; then
    echo "Need a backupdir. Please set mysql_backup_dir in confusa_config.php"
    echo "Aborting"
    exit
fi

file=/root/mysql_root.pw
if [ -f "/root/mysql_root.pw" ]; then
    pw=`cat /root/mysql_root.pw`
    res=`mysqldump -uroot -p$pw $database`
elif [ -f "/etc/mysql/debian.cnf" ]; then
    res=`mysqldump --defaults-file=/etc/mysql/debian.cnf -t $database`
fi

if [ -z "$res" ]; then
    echo "dump of database failed for some reason."
    exit
fi
d=`date +%F_%T`
if [[ ! -d "$backupdir" ]]; then
    echo "backupdir does not exists, creating"
    mkdir -p $backupdir
fi
fname="$backupdir/$database.bak.$d.gz"
echo $res | gzip > $fname
echo "Backup done, saved to $fname"
