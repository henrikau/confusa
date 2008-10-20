#!/bin/bash
#
# created by Henrik Austad, may 2008
# for SLCS-Web
# (c) UNINETT SIGMA A/S 2008
#
# A lot of the variables in this file has been set dynamically based
# upon attributes from Feide (/other fed. instances) and
# confusa_config.php.

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
country="/C="
orgname="/O=Nordugrid"
orgunitname="/OU=Nordugrid"
common="/CN="

# The following set of variables has been set dynamically by create_keyscript.php
# where the SLCS-service is located
server_loc=""
down_page=""
up_page=""

# options for downloading CSRs
wget_options=""

# where to send error-messages
error_addr=""
csr_var=""

# authentication
auth_var=""
auth_length=""


# ------------------------------------------------------------- #
# END AUTOMAGIC CONFIG
# ------------------------------------------------------------- #

# default length of RSA-key to generate
key_length=
name=`echo $common | cut -d '=' -f 2 | cut -d '@' -f 1`
script_folder=$HOME/.globus
if [ ! -f $script_folder ]; then
    mkdir -p $script_folder;
fi
priv_key_name="userkey.pem"
csr_name="userrequest.csr"
token_file="$script_folder/slcs_token"

approve_page="/key_handler.php"

download_url=$server_loc$down_page
upload_url=$server_loc$up_page

function create_key {
    openssl req -new -newkey rsa:$key_length \
	-keyout $priv_key_name -out $csr_name \
	-subj "$country$orgname$orgunitname$common"
    if [ $? -eq 0 ]; then
	echo "Key and CSR created ok"
    else
	echo -e "\n\n"
	echo -e "\t\t***** ERROR *****"
	echo "Errors where detected during the certificate creation process."
	echo "The simplest solution is probably to download a new version of this script "
	echo "from $server_loc and try again"
	echo ""
	echo -e "If the problem persist, please include the script and the "
	echo -e "output from the program and mail this to $error_adr"
	echo -e "\n\t\t***** ERROR *****"
	exit 3
    fi

}

function usage {
    echo -n "Usage of this is: "
    echo $0 "<key file-name>"
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
    echo "Token to download from server is $auth_token"
    uname=`echo $common | cut -d '=' -f 2`
    echo "getting csr from server $download_url"
    echo "Using username: $uname and auth_var $1"

    # create complete download url and download the certificate
    url="$download_url?$auth_var=$auth_token&common_name=$uname"
    cmd="wget -O tmp.cert --no-check-certificate --html-extension $url"
    res=`$cmd`
    # let openssl rip through the file to see if it's a valid certificate
    openssl x509 -in tmp.cert -text -noout > /dev/null
    if [ $? -gt 0 ];then 
	echo "Error in recovering certificate"
	echo "Log in to $server_loc$approve_page and browse through the certificates stored there"
	echo "Note the auth_token, and run "
	echo "$0 -get <auth_token>"
	exit
    else
	echo "Got certificate ok!"
	mv tmp.cert usercert.pem
    fi
}

function usage {
    echo -e "Error in parameters!\n"
    help
}

function main {
    welcome
    echo $1
    case $1 in
	-help)
	    help
	    ;;
	-new)
            echo "creating new key"
	    create_key
	    push_csr
            ;;
        -new_no_push)
            echo "creating key, but does not push to server"
	    create_key
            ;;
        -push)
            echo "pusing existing CSR to server without creating new key"
	    push_csr
            ;;
        -get)
            echo "getting certificate from server with auth_url $2"
	    get_cert $2
            ;;
        *)
            echo "Unrecognized option!"
	    help
            ;;
    esac
}
function help {
    echo "$0 <command>"
    echo -e "\t-new\tCreates a new key, generates the CSR and uploads it to the server"
    echo -e "\t-new_no_push\tCreates the new key and CSR, but does not push it to server"
    echo -e "\t-push\tPushes an existing CSR to server"
    echo -e "\t-get <auth_url>\t downloads a certificate from the server, identified by the given key"
}
function init {
    if [ $# -ge 1 ]; then 
    # check if .globus/ exists
	if [ -d !$file_location ]; then
	    echo "globus does not exist, creating...."
	    mkdir -p $HOME/.globus/
	fi
	if [ -z $common ]; then
	    echo "Need a common-name. Please download a recent version of the script from slcsweb!"
	    exit 2
	fi
	if [ -z $country ]; then
	    echo "Need a country. Download a proper script from slcsweb"
	    exit 2
	fi
	main $@
    else 
	usage
    fi
}

init $@
