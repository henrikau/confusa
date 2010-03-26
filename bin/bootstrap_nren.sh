#!/bin/bash
#
# author Thomas Zangerl <tzangerl@pdc.kth.se>
# Script for initially setting up a NREN and adding an initial admin to it,
# who may configure the NREN-settings.
#
# It is probably a good idea to run this script for new NRENs that get connected
# to Confusa.
#
# Exit-codes:
# 3: Cannot insert data in table
# 4: Cannot get requested data from database (and should be present)
# 5: Cannot include all requested files


# set path
base=`dirname $0`
pushd $base >/dev/null


#------------------------------------------------------------------------#
#		Function usage
#
# Show how to use the program and explain the meaning of the
# different parameters the script expects
#------------------------------------------------------------------------#
function usage
{
prog_name=`basename $0`
cat <<EOF
Usage $prog_name  -n <nren_name> -c <country> -u <uid-val> -e <e-mail> -a [uid-attr]
    nren_name:   The name of the NREN. Must be unique within the database.
    country:     Two-letter country-code
    uid-val:     eduPersonPrincipalName or another unique identifier
                 for an initial NREN-admin
    e-mail:      A contact information for the NREN
    uid-attr:    (Optional) The attribute that is used for transmitting the
                 unique identifier for a user
EOF
}

# make sure we have all required parameters
if [ $# -lt 4 ]; then
    usage
    exit 1
else
	while getopts "n:c:u:e:a:" opt; do
	case $opt in
		n) nren_name=$OPTARG ;;
		c) country=`echo $OPTARG | tr '[:lower:]' '[:upper:]'` ;;
		u) eppn=$OPTARG ;;
		e) contact=$OPTARG ;;
		a) eppn_key=$OPTARG ;;
		*) usage ;;
	esac
	done
fi


if   [ -z "$nren_name" ] ||
     [ -z "$country" ] ||
     [ -z "$eppn" ] ||
     [ -z "$contact" ]; then
	usage
	exit 1
fi

# Include libraries
if [ -z ../lib/bash/config_lib.sh ]; then
    echo "Cannot find config-library. Aborting."
    exit 5
fi
. ../lib/bash/config_lib.sh

if [ -z ../lib/bash/db_lib.sh ]; then
    echo "Cannot find db-library. Aborting."
    exit 5
fi
. ../lib/bash/db_lib.sh


#------------------------------------------------------------------------#
#		NREN already present?
#------------------------------------------------------------------------#
echo -ne "Is the NREN already present in the database? "
res=`run_query "SELECT nren_id FROM nrens WHERE name='$nren_name'"`
if [ -z "$res" ]; then
    echo -ne " ... no, creating ... "
    res=`run_query "INSERT INTO nrens(name, country, contact_email) VALUES('$nren_name', '$country', '$contact')"`
	result=$?
	if [ $result -ne 0 ]; then
	    echo ""
		echo "Could not insert the new NREN $1 with contact $3 into the DB"
		echo "Is the supplied data wellformed and does your confusa_config.php"
		echo "contain the right database access credentials?"
		perror $result
		exit 3
	fi
	res=`run_query "SELECT nren_id FROM nrens WHERE name='$nren_name'"`
	if [ -z "$res" ]; then
	    echo ""
	    echo "Problems getting the new NREN from the database. Cannot continue."
	    exit 4
	fi
	echo " done!"
else
    echo " ... yes"
fi
nren_id=`echo $res | cut -d " " -f 2`


#------------------------------------------------------------------------#
#		Can we add the admin
#------------------------------------------------------------------------#
res=`run_query "SELECT * FROM admins WHERE admin='${eppn}'"`
if [ -n "$res" ]; then
    cat <<EOF
An administrator with UID ${eppn} already exists in the database. Since the UID
is supposed to be an unique identifier, we cannot add this admin.

If this is not what you'd expected, you should have a look at the database and make sure that you
have provided the correct UID, and that the admin is not already present. The result from the database was:
EOF
echo $res
exit 0
fi

echo "Adding new administrator to NREN ${nren_name}, internal ID ${nren_id}"
res=`run_query "INSERT INTO admins(admin, admin_level, nren) VALUES('$eppn', '2', $nren_id)"`
result=$?

if [ $result -ne 0 ]; then
	echo "Error when inserting new admin ${2}, with contact-info ${3}, into DB"
	echo "Please check if all credentials are specified and if you supplied"
	echo "a valid unique identifier (ePPN,...) for the new admin"
	perror $result
	exit 3
fi

if [ -n $eppn_key ]; then
	echo "Now adding unique identifier ${eppn_key} to the attribute mapping of NREN ${nren_name}"
	res=`run_query "INSERT INTO attribute_mapping(nren_id, eppn) VALUES('$nren_id', '$eppn_key')"`
	result=$?

	if [ $result -ne 0 ]; then
		echo "Error when trying to add the unique identifier mapping to ${eppn_key} for NREN"
		echo "${nren_name}. Please check if you provided a legal value for the UID!"
		perror $result
		exit 3
	fi
fi

popd >/dev/null
