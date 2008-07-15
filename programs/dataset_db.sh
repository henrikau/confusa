#!/bin/bash
#
# Henrik Austad, July 2008
#
# A script that creates a set of hash-values to be inserted into a
#test-database. without arguments, it will try to insert the 2 files
#into the database.
#
# With argument, it will generate that amount of input for the database.
#
# NOTE: This is a *hack*. it is not meant for production-use, but as a
# base for evaluating the performance of different table-structures.


seed_val=`date +%s%N`
# bufferlength
# one line: 87 bytes, total 92MB to buffer in RAM
buffer=1000000
myisam_buffer=""
inno_buffer=""

# files to write to when done
myisam_file="myisam.sql"
inno_file="inno.sql"

function sval
{
    if [ -z $1 ]; then
	raw_hash=$seed_val
    else
	raw_hash=$hash
    fi
    echo $raw_hash | sha1sum | cut -d ' ' -f 1
}

function create_entries
{
    inno_buffer="$inno_bufferINSERT INTO hash_inno (pubkey_hash) VALUES('$1');"
    myisam_buffer="$myisam_bufferINSERT INTO hash_myisam   (pubkey_hash) VALUES('$1');"
}

function insert
{
    pass="`cat /root/mysql_root.pw`"
    if [ -z $pass ]; then
	echo "Error in getting password for database"
	exit
    fi
    mysql -A -u"root" -h"localhost" -p$pass -Dtest -e "DELETE FROM hash_inno"
    mysql -A -u"root" -h"localhost" -p$pass -Dtest -e "DELETE FROM hash_myisam"

    echo "inserting into InnoDB"
    time mysql -A -u"root" -h"localhost" -p$pass -Dtest < $inno_file

    echo "copying into myisam"
    time mysql -A -u"root" -h"localhost" -p$pass -Dtest < $myisam_file

}

if [ -z $1 ]; then
    echo "No argument, inserting into database"
    insert
else
    for i in `seq 1 $1`; do 
	if [ $(($i % $buffer)) -eq 0 ]; then
	    echo $inno_buffer >> $inno_file
	    echo $myisam_buffer >> $myisam_file
	    inno_buffer=""
	    myisam_buffer=""
	fi
	if [ $(($i % 100)) -eq 0 ]; then
	    echo "$0 `date +%H:%M:%S`: $i iterations completed of total $1"
	fi
	hash=`sval $hash`
	create_entries $hash
    done
    echo $inno_buffer >> $inno_file
    echo $myisam_buffer >> $myisam_file
fi

