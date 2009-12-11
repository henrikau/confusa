#!/bin/bash
#
# Use this script to mark critical errors in Confusa that have also been
# reported to the outside as "resolved" or delete (purge) them from the
# database altogether.
# using getopts
#


purge=0
interact=0
resolve=0

function usage {
	echo -e "\tUsage: $0 [-i | -a [purge|resolve]]"
	echo -e "\t-i:\t\tInteractive. Ask for every unresolved critical error in"
	echo -e "\t\t\tthe DB about the action."
	echo -e "\t-a purge:\tDelete all critical errors from the DB"
	echo -e "\t-a resolve:\tMark all critical errors as resolved."
	exit 0
}

while getopts 'ia:' OPTION
do
	case $OPTION in
	a)	if [ $OPTARG = "purge" ]; then
			purge=1
		elif [ $OPTARG = "resolve" ]; then
			resolve=1
		else
			echo "-a $OPTARG is not a legal option! Cowardly refusing the unknown!"
			echo ""
			usage
			exit 1
		fi

		;;
	i)	interact=1
		;;
	?)	usage
		;;
	esac
done
shift $(($OPTIND -1))

source $(dirname $0)/../lib/bash/config_lib.sh
source $(dirname $0)/../lib/bash/db_lib.sh


# Call this function for interactive questions with the questions as an argument
function get_user_action
{
	answer=""

	while [ -z $answer ]; do
		echo -n "(s)kip (p)urge (m)ark resolved: "
		read answer
		case $answer in
			"s"|"p"|"m") break ;;
		esac

		answer=""
	done
}

# interactive mode, ask for every critical log-line whether to resolve, skip or
# purge it
if [ $interact -eq 1 ]; then
	res=`run_query "SELECT errid, error_date, log_msg FROM \
	                   critical_errors WHERE is_resolved=false"`
	oldifs="$IFS"
	IFS=$'\n'
	lines=( $res )
	IFS=$oldifs

	# The first line will be the column-names
	for (( i=0;i<${#lines[@]};i++ ))
	do
		echo "Next critical error: ${lines[${i}]}"
		get_user_action

		if [ $answer = "s" ]; then
			echo "Skipping critical error with ID $errid"
			echo ""
			continue
		elif [ $answer = "p" ]; then
			errid=`echo ${lines[${i}]} | cut -d " " -f 1`
			echo "Purging critical error with ID $errid from DB"
			echo ""
			${MYSQL} -e "DELETE FROM critical_errors WHERE errid=$errid"
			result=$?
		elif [ $answer = "m" ]; then
			errid=`echo ${lines[${i}]} | cut -d " " -f 1`
			echo "Marking critical error with ID $errid resolved in DB"
			echo ""
			${MYSQL} -e "UPDATE critical_errors SET \
			             is_resolved=true WHERE errid=${errid}"
			result=$?
		else
			echo "Unknown option chosen. Cowardly refusing the unknown!"
			echo ""
			continue
		fi

		if [ ! $result -eq 0 ]; then
			echo -e "Last operation failed! Please check your DB connection"
			echo -e "information in dbconfig/confusa_config or slap the author"
			echo "of this script."
		fi
	done

else # non-interactive mode
	if [ $purge -eq 1 ]; then
		echo "Purging all critical errors from the database!"
		$MYSQL -e "DELETE FROM critical_errors"
		result=$?
	elif [ $resolve -eq 1 ]; then
		echo "Setting all critical error in the DB to 'resolved'."
		$MYSQL -e "UPDATE critical_errors SET is_resolved=true"
		result=$?
	else
		echo -e "Resolve errors called in neither interactive mode, nor purge "
		echo "mode, nor resolve mode. Cowardly refusing the unknown."
		echo ""
		usage
		exit 1
	fi

	if [ $result -ne 0 ]; then
		echo -e "Could not update the critical_errors table. Do you have the "
		echo "correct database credentials specified in dbconfig/confusa_config?"
		perror $result
		exit 1
	fi
fi
