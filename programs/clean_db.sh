#!/bin/bash
# Program intended as cron-job for cleaning the database in a periodic interval
#
# Author: Henrik Austad <henrik.austad@uninett.no>
#
function usage
{
    echo "Usage: $0 <config-file>"
    exit
}
if [ ! $# -eq 1 ]; then
    echo "Errors in parameters, need exactly 1, $# given"
    usage
fi

if [ ! -f $1 ]; then
    echo "config-file does not exist"
    usage
fi

configfile=$1
function get_val
{
    if [ -z $1 ]; then
	return;
    fi
    grep "$1" $configfile | cut -d '=' -f 2 | cut -d "'" -f 2
}

function get_array_vals
{
    if [ -z $1 ]; then
    return;
    fi
    grep "$1" $configfile | cut -d "(" -f 2 | cut -d ")" -f 1
}

user=`get_val "mysql_username"`
pass=`get_val "mysql_password"`
host=`get_val "mysql_host"`
database=`get_val "mysql_db"`

db_auth="-A -u$user -h$host -p$pass -D$database"

csr_timeout=`get_array_vals "csr_default_timeout"`
csr_timeout_value=`echo $csr_timeout | cut -d "," -f 1`
csr_timeout_unit=`echo $csr_timeout | cut -d "," -f 2 | cut -d "'" -f 2`

csr_cache="DELETE FROM csr_cache WHERE current_timestamp() > timestampadd($csr_timeout_unit, $csr_timeout_value, uploaded_date)";
cert_cache="DELETE FROM cert_cache WHERE valid_untill < current_timestamp()"
order_cache="DELETE FROM order_cache WHERE expires < current_timestamp()"

mysql $db_auth "-e $csr_cache"  || echo "could not clean csr_cache"
mysql $db_auth "-e $cert_cache" || echo "could not clean cert_cache"
mysql $db_auth "-e $order_cache" || echo "could not clean order_cache"
