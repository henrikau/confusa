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
	error_exit "120" "Need the config-file ($1), supported file does not exist"
    fi
}

if [ ! $# -eq 2 ]; then
    echo "1"
    echo "need more arguments (auth_key of CSR to sign and path to ready certificate)" >&2
    exit 1
fi

# Move to the directory where the script resides
pushd `dirname $0` > /dev/null

include_file "../lib/bash/output.sh"
include_file "../lib/bash/config_lib.sh"
include_file "../lib/bash/db_lib.sh"

csr=`run_query "SELECT csr FROM csr_cache where auth_key='$1'"|grep -E '|.*|'`
if [ "$csr" == "" ]; then
    error_exit "121" "No CSR found"
fi

# Store the CSR in a temporary file
tmpfile=`mktemp /var/tmp/csr.XXXXXX`
echo -ne $csr > $tmpfile
if [ ! -s $tmpfile ]; then
    error_exit "122" "$tmpfile has no content"
fi
if [ ! -s $tmpfile ]; then
    error_exit "126" "$tmpfile is not readable for webserver"
fi
# Sign the CSR and write to provided file
cacert="`pwd``get_config_entry 'ca_cert_path'``get_config_entry 'ca_cert_name'`"
if [ ! -f "$cacert" ]; then
    error_exit "123" "CA-cert not set"
fi
if [ ! -r "$cacert" ]; then
    error_exit "125" "CA-cert not readable for webserver"
fi

cakey="`pwd``get_config_entry 'ca_key_path'``get_config_entry 'ca_key_name'`"
if [ ! -f "$cakey" ]; then
    error_exit "124" "CA-key not set!"
fi

if [ ! -r "$cakey" ]; then
    error_exit "127" "CA-key not readable for webserver"
fi

if [ ! -w "$2" ]; then
    error_exit "128" "Target certificate-file is not writable for webserver user!"
fi

openssl x509 -req -days 395 -in $tmpfile -CA $cacert -CAkey $cakey -CAcreateserial -out $2

# Remove the tmp-file and return to original dir (just to be sure)
rm -f $tmpfile
popd > /dev/null
echo 0
