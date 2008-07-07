#!/bin/sh
#
# sign_key.sh
# script for signing a CSR by the confusa-priv key
# This is intented as a hack untill proper key-signing is in place.
#debug="-verbose"

# make sure the arguments exists
if [ $# -ne 1 ]; then
    echo "Usage: $0 <cert>"
    exit 1
fi
if [ ! -f $1 ]; then
    echo "CSR not found: $1"; 
    exit 1
fi
CSR=$1
case $1 in
    *.csr)
	CERT=`echo $CSR | sed s/.csr/.crt/`
	;;
    *.pem)
	CERT=`echo $CSR | sed s/.pem/.crt/`
	;;
    *)
	CERT=`echo $CSR.crt`
	;;
esac
cat "" > $CERT
chmod a+w $CERT
whoami

# make sure environment exists
if [ ! -d ca.db.certs ]; then
    mkdir ca.db.certs
fi
if [ ! -f ca.db.serial ]; then
    echo '01' >ca.db.serial
fi
if [ ! -f ca.db.index ]; then
    cp /dev/null ca.db.index
fi

add_time ()
{
    date --utc --date "`date --utc` $1 sec" +%y%m%d%H%M%S
}

sign_cert ()
{
openssl ca -config conf/confusa_openssl.conf \
    -batch \
    -startdate "$startdate" \
    -enddate "$enddate" \
    $debug \
    -out $CERT \
    -infiles $CSR

if [ $? -eq 0 ]; then
    echo "Signing ok, without hiccups!"
#     echo -n "Verifying certificate: "
#     cat $CERT
    openssl verify -CAfile cert/sigma_cert.pem $CERT
    return 0
else
    echo "Errors were encountered while signing the certificate!"
    return 2
fi

}

startdate="`add_time 0`Z"
enddate="`add_time 1000000`Z"

#  sign the certificate
echo "CA signing: $CSR -> $CERT:"
sign_cert
res=$? 
