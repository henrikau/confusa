#!/bin/bash
#
#		Henrik Austad
#	UNINETT SIGMA A/S 2008,2009
#
# This is part of the Confusa setup. This file is released,
# as the rest of Confusa, under GPLv3
#
#

# ------------------------------------------------------------- #
#		BEGIN AUTOMAGIC CONFIG
#
# If some (or all) of the variables below this point are empty
# contact the maintaner of the site that sent you this script.
#
# Most likely the automatic parsing failed, and trying to download
# the script again will solve the problem.
# 
# However, if the problem persists, and you *really* need a
# certificate *right now*, and you know what you are doing - try to set
# the fields manually with data from the site (attributes etc).
# ------------------------------------------------------------- #

# Fields for the certificate
country=""
orgname=""
orgunitname=""
common=""

# The key must have a length, and Confusa imposes a minimum length,
# changing this to a lower value will most likely cause the key (CSR) to
# be rejected by Confusa
key_length=

# where the Confusa instance is running, and which sites that handles
# the respective parts of the operation
server_loc=""
down_page=""
up_page=""
approve_page=""

# The CA has it's own certificate
ca_cert_name=""
ca_cert_path=""

# For upload (GET with base64) and download, we use wget. In order to
# make this more seamless (less noise on the user terminal) we set some
# options from server-side. You may change this without affecting the
# script
wget_options=""

# If something goes (seriously) wrong, this is the error-address
# given. You will be told to contact this address
error_addr=""

# the name of the csr-var we need to supply to the upload-page so that
# it will trigger properly
csr_var=""

# the name of the auth-token and the length of it
auth_var=""
auth_length=""

# ------------------------------------------------------------- #
# END AUTOMAGIC CONFIG
# ------------------------------------------------------------- #

name=`echo $common | cut -d '=' -f 2 | cut -d '@' -f 1`
script_folder=$HOME/.globus

if [ ! -f $script_folder ]; then
    mkdir -p $script_folder;
fi
priv_key_name="$script_folder/userkey.pem"
csr_name="$script_folder/userrequest.csr"
cert_name="$script_folder/usercert.pem"
token_file="$script_folder/slcs_token"


download_url=$server_loc$down_page
upload_url=$server_loc$up_page

# Create full CA Cert name (locally)
fccn=$script_folder/$ca_cert_name

# ------------------------------------------------------------- #
# test_automagic_variables
#
# Function for testing if all the necessary automagic variables is
# filled in properly. If they are not, the script will abort with a
# notification to the user as to why the execution was stopped.
# ------------------------------------------------------------- #
function test_automagic_variables {
    error_tail="\nDownload a new version and try again"
    if [ -z $country ]; then
	exit_error "Country-attribute (C) for certificate not set $error_tail"
    fi
    if [ -z $orgname ]; then
	exit_error "OrganizationalName-attribute (O) for certificate not set $error_tail"
    fi
    if [ -z $orgunit ]; then
	exit_error "OrganizationalUnitName-attribute (OU) for certificate not set $error_tail"
    fi
    if [ -z $common ]; then
	exit_error "CommonName-attribute (CN) for certificate not set $error_tail"
    fi
    if [ -z $key_length ]; then
	exit_error "Length for key not set $error_tail"
    fi
    if [ -z $server_loc ]; then
	exit_error "No server-address provided $error_tail"
    fi
    if [ -z $down_page ]; then
	exit_error "No download page for signed certificates provided $error_tail"
    fi
    if [ -z $up_page ]; then
	exit_error "No upload page for CSRs provided $error_tail"
    fi
    if [ -z $approve_page ]; then
	echo "No approve page provided. This is not a critical error,"
	echo "but it means that *you* must know where to find it - this"
	echo "script will not be able to tell you"
	echo ""
    fi
    if [ -z $ca_cert_name ]; then
	echo "Cannot verify CA-certificates - do not know the name of "
	echo "the CA certificate"
	echo ""
    fi
    if [ -z $ca_cert_path ]; then
	echo "Cannot verify CA ertificates - do not know the path to"
	echo "the CA certificate"
	echo ""
    fi
    if [ -z $wget_options ]; then
	echo "No wget-options set - are you sure this is intentional?"
	echo ""
    fi
    if [ -z $error_addr ]; then
	echo "No error-address set"
	echo ""
    fi
    if [ -z $csr_var ]; then
	exit_error "No name for CSR set - cannot upload CSR to server $error_tail"
    fi
    if [ -z $auth_var ]; then
	exit_error "No name for auth-var - cannot upload CSR or download certificates $error_tail"
    fi
    if [ -z $auth_length ]; then
	exit_error "Length of auth_var not set - cannot determine proper authentication variable $error_tail"
    fi
}

# ------------------------------------------------------------- #
# test_cert_expired
#
# Test to see if a given certificate is expired
# ------------------------------------------------------------- #
function test_cert_expired {
    # Make sure CA cert is present. If not obtainable, exit
    echo "Full CA certfile name (locally): $fccn"
    test -f $fccn || wget -O $fccn $wget_options $server_loc/$ca_cert_path$ca_cert_name || rm $fccn
    test -f $fccn || exit_error "Unable to get CA Certificate"
    res=`openssl verify -verbose -CAfile $fccn $1 | grep error`
    if [ ! "$res" == "" ]; then
	return 1
    fi
    return 0
}

# ------------------------------------------------------------- #
# clean_globus
#
# Remove all old keys and certificates from script_folder (per def
# $HOME/.globus/
# ------------------------------------------------------------- #
function clean_globus {
    echo ""
    echo "Cleaning up $script_folder"
    # remove backups
    rm -f $script_folder/*~
    rm -f $script_folder/\#*
    # test to see if current cert is expired:
    tmp=$cert_name
    test_cert_expired $tmp
    if [ "$?" -gt 0 ]; then
	echo "removing current private key and (expired) certificate"
	test -f $priv_key_name && rm -f $priv_key_name
	test -f $cert_name && rm -f $cert_name
    fi
}

function create_key {
    openssl req -new -newkey rsa:$key_length \
	-keyout $priv_key_name -out $csr_name \
	-subj "$country$orgname$orgunit$common"
    if [ $? -eq 0 ]; then
	echo "Key and CSR created ok"
    else
	str=""
	str="$str Errors where detected during the certificate creation process.\n"
	str="$str The simplest solution is probably to download a new version of this script\n"
	str="$str from $server_loc and try again\n\n"
	str="$str If the problem persist, please include the script and the\n"
	str="$str output from the program and mail this to $error_adr\n"
	exit_error "$str"
    fi

}

function welcome {
    echo -e "\n"
    echo "***************************************************************************"
    echo -e "\n\tThis script creates the key needed for middleware usage. "
    echo -e "\tIf you do not want to use the SLCS-service, please use the "
    echo -e "\ttraditional ARC middleware scripts\n"
    echo -e "\n\tFor help, type $0 -help\n"
    echo "***************************************************************************"
    echo -e "\n"
    #echo "Do you wish to contiune [Y/n]?"
}

function push_csr {
    # create base64-encoding
    base_csr=`cat $csr_name | openssl base64`
    base_csr=`echo $base_csr | sed s/\ //g`

    # create auth-url
    auth_url=`date +%s | sha1sum | cut -d ' ' -f 1`
    auth_token=${auth_url:0:$auth_length}
    url="$upload_url?$auth_var=$auth_token&$csr_var=$base_csr"

    # push to server, ignore output (only confuses the user)
    res=`wget $wget_options "$url" 2>&1`
    
    # store auth_token for later usage, to ease the usage of -get
    echo $auth_token > $token_file

    echo ""
    echo "CSR uploaded to server. You should now log in to $server_loc and approve the CSR with this AuthToken: $auth_token"
    echo "The easiest way is to use:"
    echo ""
    echo "$server_loc$approve_page?auth_token=$auth_token"
    echo ""
}

function get_cert {
    if [ -z $1 ]; then
	if [ ! -f $token_file ]; then
	    echo "Cannot download without auth_url"
	    return
	else
	    auth_token=`cat $token_file`
	fi
    else
	    auth_token=$1
    fi
    uname=`echo $common | cut -d '=' -f 2`

    # create complete download url and download the certificate
    url="$download_url?$auth_var=$auth_token&common_name=$uname"
    echo "getting url: $url"
    cmd="wget -O tmp.cert $wget_options --html-extension $url"
    res=`$cmd`
    # let openssl rip through the file to see if it's a valid certificate
    openssl x509 -in tmp.cert -text -noout > /dev/null
    if [ $? -gt 0 ];then 
	str=""
	str="$str Error in recovering certificate"
	str="$str Log in to $server_loc$approve_page and browse through the certificates stored there"
	str="$str Note the auth_token, and run "
	str="$str $0 -get <auth_token>"
	exit_error "$str"
    else
	echo "Got certificate ok!"
	mv tmp.cert $cert_name
    fi
}

function main {
    welcome
    echo $1
    case $1 in
	-help)
	    cc_help
	    ;;
	-new)
            # echo "creating new key"
	    create_key
	    push_csr
            ;;
        -new_no_push)
            # echo "creating key, but does not push to server"
	    create_key
            ;;
        -push)
            # echo "pusing existing CSR to server without creating new key"
	    push_csr
            ;;
        -get)
	    get_cert $2
            ;;
	-clean)
	    clean_globus
            ;;
        *)
            echo "Unrecognized option!"
	    cc_help
            ;;
    esac
}
function cc_help {
    echo "$0 <command>"
    echo -e "\t-new\t\t\tCreates a new key, generates the CSR and uploads it to the server"
    echo -e "\t-new_no_push\t\tCreates the new key and CSR, but does not push it to server"
    echo -e "\t-push\t\t\tPushes an existing CSR to server"
    echo -e "\t-get \t[<auth_url>]\tdownloads a certificate from the server, identified by the given token"
    echo -e "\t\t\t\tif no token is given, `basename $0` uses $token_file"
    echo -e "\t-help\t\t\tthis help text\n"
}

# ------------------------------------------------------------- #
# exit_error
#
# Simple error handler. Prints the message provided and exits
# ------------------------------------------------------------- #
function exit_error {
    echo ""
    echo -ne "**********  ERROR  **********\n\n"
    echo -ne "$1\n\n"
    echo -ne "**********  ERROR  **********\n"
    exit 1
}

function init {
    if [ $# -ge 1 ]; then 
    # check if .globus/ exists
	if [ -d !$file_location ]; then
	    echo "globus does not exist, creating...."
	    mkdir -p $HOME/.globus/
	fi
	if [ -z $common ]; then
	    exit_error "Need a common-name. Please download a recent version of the script from slcsweb!"
	fi
	if [ -z $country ]; then
	    exit_error "Need a country. Download a proper script from slcsweb"
	fi
	main $@
    else 
	cc_help
    fi
}

test_automagic_variables
init $@
