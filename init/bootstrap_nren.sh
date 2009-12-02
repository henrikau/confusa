#!/bin/bash
#
# author Thomas Zangerl <tzangerl@pdc.kth.se>
# Script for initially setting up a NREN and adding an initial admin to it,
# who may configure the NREN-settings.
#
# It is probably a good idea to run this script for new NRENs that get connected
# to Confusa.
#


if [ $# != 3 ]; then
	echo -e "\tUsage: $0 <nren_name> <principal> <contact>"
	echo -e "\tnren_name:\tThe name of the NREN, e.g. UNINETT"
	echo -e "\tprincipal:\teduPersonPrincipalName or another unique identifier for \n\t\t\tan initial NREN-admin"
	echo -e "\tcontact:\tA contact information for the NREN"
	exit 1
fi;

source ../programs/db_connect.sh

function get_nren_id
{
	res=`$MYSQL -e "USE ${database}; SELECT nren_id FROM nrens WHERE name='$1'"`
	result=$?

	if [ $result -ne 0 ]; then
		echo "Could not lookup NREN $1 in the database. Do you have the correct"
		echo "database credentials specified there?"
		perror $result
		exit 1
	fi
}

# Try to find the NREN in the DB

echo "Looking up if NREN was already defined"
get_nren_id $1
if [ -n "$res" ]; then
	echo "NREN was found, new administrator will be added to it"
elif [ -z "$res" ]; then
	echo "NREN not found, newly inserting NREN into the database"
	res=`$MYSQL -e "USE ${database}; INSERT INTO nrens(name, contact_email) \
		VALUES('$1', '$3')"`
	result=$?

	if [ $result -ne 0 ]; then
		echo "Could not insert the new NREN $1 with contact $3 into the DB"
		echo "Is the supplied data wellformed and does your confusa_config.php"
		echo "contain the right database access credentials?"
		perror $result
		exit 1
	fi

	get_nren_id $1
fi

nren_id=`echo $res | cut -d " " -f 2`

res=`$MYSQL -e "USE ${database}; SELECT * FROM admins WHERE admin='${2}' AND nren=${nren_id}"`

if [ -n "$res" ]; then
	echo "ERROR: An administrator with eppn ${2} already exists for NREN ${1}. Aborting..."
	exit 1
fi

echo "Adding new administrator to NREN $1, internal ID $nren_id"
res=`$MYSQL -e "USE ${database}; INSERT INTO admins(admin, admin_level, admin_email, nren) \
		VALUES('$2', '2', '$3', $nren_id)"`
result=$?

if [ $result -ne 0 ]; then
	echo "Error when inserting new admin ${2}, with contact-info ${3}, into DB"
	echo "Please check if all credentials are specified and if you supplied"
	echo "a valid ePPN for the new admin"
	perror $result
	exit 1
fi

echo "NREN-administrator successfully bootstrapped"
