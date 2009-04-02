#!/bin/bash
# Henrik Austad ,2009
# UNINETT Sigma A/S
#
# make_cmc.sh
#
# Shell-wrapper for making CMC's to send to a dogtag system
# This script uses CMCEnroll to create CMCs, and it creates simple CMCs,
# i.e. no bundling of several CSRs together.
#

# What the script shall do:
#
# Need input to tmp-file containing the CSR
# If it's on PEM-format, convert it to DER
# run it through BtoA to get it back to ascii-encoded
# Give it to CMCEnroll to create the CMC
# Return the CMC to confusa and let confusa talk to dogtag.

# Retrieve elements from the config-directory.
configfile="../config/confusa_config.php"
function get_config_param()
{
    if [ $# -lt 1 ]; then
	echo "Need name of config-parameter to retrieve!">&2
	return 1
    fi
    if [ ! -f $configfile ]; then
	echo "confusa-config.php not set. Need this file to continue">&2
	return 1
    fi
    grep "$1" $configfile | cut -d '>' -f 2 | cut -d "'" -f 2
    return 0
}

res=`get_config_param valid_install`
if [ ! "$?" -eq "0" ]; then
    echo "Error in getting valid_install from config"
    exit 127
fi

cmccertdir=`get_config_param cmc_cert_dir`
if [ ! -d $cmccertdir ]; then
    echo "CMC Certdir not present. Aborting." >&2
    echo "Looking for $cmccertdir" >&2
    echo "Run certutil and create the keystore for signing the CMC's" >&2
    exit 127
fi

cmc_pw=`get_config_param cmc_agent_pw` || exit 122
cmcnick=`get_config_param cmc_agent_nick` || exit

function usage ()
{
    echo "Usage: $0 <csr_file>"
    echo -ne "\t<csr_file>\tFull path and name to the CSR\n"
    exit $1
}

if [ $# -lt 1 ]; then
	echo "Need path to CSR!" >&2
	usage 1
fi
if [ ! -f $1 ]; then
	echo "File ($1) does not exist!" >&2
	usage 2
fi

# convert from binary to ascii
bname=`mktemp -t csr_in_der.XXXXXX`
aname=`mktemp -t csr_in_a.XXXXXX`

# test to see if input is PEM, if so, convert to DER
if openssl req -in $1 > /dev/null 2>&1 ; then
    openssl req -in $1 -inform PEM -outform DER -out $bname || exit
    echo "Converted PEM-certificate to DER" >&2
elif !openssl req -in $1 > /dev/null 2>&1 -inform DER; then
    echo "Not DER either, this CSR is malformed!">&2
    exit 126
fi
BtoA $bname $aname || exit

CMCEnroll -d $cmccertdir -n "$cmcnick" -r $aname -p "$cmc_pw"

# remove tmp-files and the original CSR-file
rm -f $1
rm -f $bname
rm -f $aname
