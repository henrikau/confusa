#!/bin/bash
#
#		Henrik Austad
#	UNINETT SIGMA A/S 2008,2009
#	
#	Part of Confusa, GPLv3 applies.
# 
#
# Script for signing keys, the auth_token to the CSR in the database
# must be passed along as an argument, the same with the filename where
# openssl should store the certificate.

function include_file ()
{
    # Include the config-lib
    if [ -f $1 ]; then
	. ./$1
    else
	echo "Need $1"
	exit 127
    fi
}

if [ ! $# -eq 2 ]; then
    echo "need more arguments (auth_key of CSR to sign and path to ready certificate)" >&2
    exit 1
fi

# Move to the directory where the script resides
pushd `dirname $0` > /dev/null

include_file "../lib/bash/config_lib.sh"
include_file "../lib/bash/db_lib.sh"

csr=`run_query "SELECT csr FROM csr_cache where auth_key='$1'"|grep -E '|.*|'`
if [ "$csr" == "" ]; then
    echo "No CSR found"
    exit 127
fi

# Store the CSR in a temporary file
tmpfile=`mktemp /tmp/csr.XXXXXX`
echo -ne $csr > $tmpfile

# Sign the CSR and write to provided file
cacert=".`get_config_entry 'ca_cert_path'``get_config_entry 'ca_cert_name'`"
if [ $cacert == "" ]; then
    exit 127
fi
cakey=".`get_config_entry 'ca_key_path'``get_config_entry 'ca_key_name'`"
if [ $cakey == "" ]; then
    exit 127
fi

openssl x509 -req -days 395 -in $tmpfile -CA $cacert -CAkey $cakey -CAcreateserial -out $2

# Remove the tmp-file and return to original dir (just to be sure)
rm -f $tmpfile
popd > /dev/null
