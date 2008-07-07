#!/bin/bash
#
# (c) Henrik Austad, UNINETT Sigma A/S
# 13. May 2008
#
# This script can also update the apache-ssl directory with the new CA
# (if you want to use the same certificate you use to sign the keys).
#
# This is intended for testing purposes only (!)
#
country="/C=NO"
state="/ST=Sor-Trondelag"
city="/L=Trondheim"
orgname="/O=Nordugrid"
orgunitname="/OU=UNINETT Sigma A\/S"
#orgunitname="UNINETT Sigma"
common="/CN=slcstest.uninett.no"

# vars:
priv_key=sigma_priv_key.pem
priv_path=priv
cert=sigma_cert.pem
cert_path=cert
public_cert_path=../web/www/certs

# apache
apache_priv_key=slcstest.key
apache_cert=slcstest.crt
apache_path=/etc/apache2/ssl

# extra openssl flags
flags=""
days=3650

welcome () 
{
    echo -e "\n\nScript for creating a key (and certificate) for test-ca'ing\n\n"
    echo -e "Do *NOT* use this to create certificate for a full-fledged production CA\n\n"
}

# generate root-certificate (self-signed)
create_cert ()
{
    if [ ! -d $cert_path ];then
	mkdir $cert_path
    fi
    if [ ! -d $priv_path ];then 
	mkdir $priv_path
    fi
    if [ ! -d $public_cert_path ];then
	mkdir -p $public_cert_path
    fi

    openssl req -new -x509 -extensions v3_ca \
	-keyout $priv_path/$priv_key \
	-out $cert_path/$cert \
	-days $days \
	-subj "$country$state$city$orgname$orgunitname$common" \
	$flags \
	-config ./conf/confusa_openssl.conf
}

read_command_line ()
{
    while [ "X$1" != "X" ]
    do
	case $1 in
	    -newca)
		echo "creating new CA certificate"
		create_cert
		update_signing
		shift
		;;
	    -days)
		days=$2
		shift
		shift
		echo "new days : $days"
		;;
	    -nopass)
		flags="-nodes $flags"
		shift
		;;
	    -apache)
		update_apache
		shift
		;;
	    *)
		echo "unrecognized option $1"
		shift
		;;
	esac
    done
}


update_apache () 
{
    echo "Copying old files"
    datetime=`date +%F-%R`
    echo "Moving: "
    echo "$apache_path/$apache_cert $apache_path/$datetime-$apache_cert"
    echo "$apache_path/$apache_priv_key $apache_path/$datetime-$apache_priv_key"
    cp $apache_path/$apache_cert $apache_path/$datetime-$apache_cert
    cp $apache_path/$apache_priv_key $apache_path/$datetime-$apache_priv_key
    chmod 0400 $apache_path/$datetime-$apache_cert
    chmod 0400 $apache_path/$datetime-$apache_priv_key

    echo "Copying certifcates to apache-dir."
    cp $cert_path/$cert		$apache_path/$apache_cert
    cp $priv_path/$priv_key	$apache_path/$apache_priv_key
    

    echo "You will need to restart apache for these changes to take effect"
}

update_signing ()
{
    cp $cert_path/$cert $public_cert_path/.
    echo -e "To inspect certificate: \nopenssl x509 -in $cert_path/$cert -text | less\n"
    echo -e "CA certificate completed. Use sign_key.sh <path-to-csr> to sign certificates\n\n"
}

welcome
read_command_line "$@"
