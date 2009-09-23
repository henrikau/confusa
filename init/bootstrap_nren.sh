#!/bin/sh

if [ $# != 3 ]; then
	echo -e "\tUsage: $0 <nren_name> <principal> <contact>"
	echo -e "\tnren_name:\tThe name of the NREN, e.g. UNINETT"
	echo -e "\tprincipal:\teduPersonPrincipalName or another unique identifier for \n\t\t\tan initial NREN-admin"
	echo -e "\tcontact:\tA contact information for the NREN"
	exit 1
fi;

php -f bootstrap_nren.php $1 $2 $3
