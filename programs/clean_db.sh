#!/bin/bash
function usage
{
    echo "Usage: $0 <config-file>"
    exit
}
if [ ! $# -eq 1 ]; then
    echo "Errors in parameters, need exactly 1, $# given"
    usage
fi
echo $1
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
user=`get_val "mysql_username"`
pass=`get_val "mysql_password"`
host=`get_val "mysql_host"`
database=`get_val "mysql_db"`
echo $user $pass $host $database
db_auth="-A -u$user -h$host -p$pass -D$database"

csr_timeout=`get_val "csr_default_timeout"`

csr_cache="DELETE FROM csr_cache WHERE current_timestamp() > addtime(uploaded_date, '$csr_timeout')";
cert_cache="DELETE FROM cert_cache WHERE valid_untill < current_timestamp()"
sms_auth="DELETE FROM sms_auth WHERE valid_untill < current_timestamp()"

mysql $db_auth "-e $csr_cache"
mysql $db_auth "-e $cert_cache"
mysql $db_auth "-e $sms_auth"
