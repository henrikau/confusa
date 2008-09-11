#!/bin/bash
if [ ! `whoami` == "root" ]; then
    echo "Should be root"
fi
if [ ! -f "../config/confusa_config.php" ]; then
    echo "cannot dump database before confusa_config exists!"
    exit
fi

# find database to do backup from
database=`grep "mysql_db" ../config/confusa_config.php | cut -d '=' -f 2\
    | cut -d "'" -f 2`
if [ -z "$database" ]; then
    echo "Cannot find configured database in confusa_config. Aborting"
    exit
fi

# find backupdir
backupdir=`grep "mysql_backup_dir" ../config/confusa_config.php | cut -d '=' -f 2\
    | cut -d "'" -f 2`"/"
if [ -z "$backupdir" ]; then
    echo "Need a backupdir. Please set mysql_backup_dir in confusa_config.php"
    echo "Aborting"
    exit
fi


# test for debian-sys-maint
file=/etc/mysql/debian.cnf
if [ -f $file ]; then
    user=`grep user $file | cut -d '=' -f 2 | head -n 1 |sed s/'\ '//g`
    pw=`grep password $file | cut -d '=' -f 2 | head -n 1 |sed s/'\ '//g`
else
    echo "debian-sys-main not set. Aborting"
    exit
fi
res=`mysqldump -u$user -p$pw $database`
if [ -z "$res" ]; then
    echo "dump of database failed for some reason."
    exit
fi
d=`date +%F_%T`
fname=$backupdir$database"_backup_$d"
echo $fname
echo $res > $fname