#!/bin/bash
user="root"
pass="`cat /root/mysql_root.pw`"
database="uninett"
host="localhost"

db_auth="-A -u$user -h$host -p$pass -D$database -e"
cert_cache="DELETE FROM cert_cache WHERE valid_untill < current_timestamp()"
sms_auth="DELETE FROM sms_auth WHERE valid_untill < current_timestamp()"

mysql $db_auth "$cert_cache"
mysql $db_auth "$sms_auth"
