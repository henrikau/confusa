#!/bin/bash
seed_val=`date +%s%N`
inno_file="hash_inno.sql"
myisam_file="hash_myisam.sql"

function sval
{
    if [ -z $1 ]; then
	raw_hash=$seed_val
    else
	raw_hash=$hash
    fi
    echo $raw_hash | sha1sum | cut -d ' ' -f 1
}
function insert
{
    if [ $1 -gt 0 ]; then
	hash=`sval $2`
	tmp=$1
	((tmp--))
	echo "INSERT INTO hash_inno   (pubkey_hash) VALUES('$hash');" >> $inno_file
	echo "INSERT INTO hash_myisam (pubkey_hash) VALUES('$hash');" >> $myisam_file
	insert $tmp $hash
    fi
}

if [ -z $1 ]; then
    echo "You must specify how many iterations the program should run"
    exit
fi

# reset files
if [ -f $inno_file ]; then
    rm -f $inno_file
fi

if [ -f $myisam_file ]; then
    rm -f $myisam_file
fi


# run program for given rounds, using time as seed for hash-function
insert $1
