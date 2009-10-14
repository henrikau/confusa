#!/bin/bash
#
#		Henrik Austad
#	UNINETT SIGMA A/S 2008,2009
#
#	Part of Confusa, GPLv3 applies.
#
#
# Script for revoking a certificate found in the database with a
# provided auth_key. It will update the CRL-file in the local
# cert_handle dir, but not move it to a public location. This is the
# responsibility of other routines.
#
# When presented with a auth_key, this script will retrieve this from
# the database and revoke it. It will *not* test for ownership, if this
# is called, *that* certificate is revoked. Period.

# Get the needed files
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
    echo "need more arguments (auth_key of Certificate in database and path to the CRL file)" >&2
    exit 1
fi

# Move to the directory where the script resides
pushd `dirname $0` > /dev/null

# Get libraries
include_file "../lib/bash/config_lib.sh"
include_file "../lib/bash/db_lib.sh"

# Get files needed for the revocation
config=".`get_config_entry 'ca_conf_name'`"
cacert=".`get_config_entry 'ca_cert_path'``get_config_entry 'ca_cert_name'`"
cakey=".`get_config_entry 'ca_key_path'``get_config_entry 'ca_key_name'`"
crlfile=$2

cert=`run_query "SELECT cert FROM cert_cache where auth_key='$1'"|grep -E '|.*|'`
tmpfile=`mktemp /tmp/cert_XXXXXX`
echo -ne $cert > $tmpfile

openssl ca -config $config -revoke $tmpfile -keyfile $cakey -cert $cacert
if [ ! $? -eq 0 ]; then
    echo ""
    echo "Could not revoke certificate properly. Is it already revoked?"
    echo ""
    exit 127
fi

openssl ca -gencrl -config $config -keyfile $cakey -cert $cacert -out $crlfile
if [ ! $? -eq 0 ]; then
    echo ""
    echo "Errors when updating the CRL-file"
    echo "Does the user `whoami` have access to the file?"
    echo  $crlfile
    echo ""
    exit 127
fi
# Cat the file (to let php capture it)
rm -f $tmpfile
popd > /dev/null
exit 0
