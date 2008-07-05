#!/bin/bash
user="root"
pass="`cat /root/mysql_root.pw`"
database="uninett"
host="localhost"


# mysql -u$user -h$host -p$pass -e$create_db;
mysql -A -u$user -h$host -p$pass -D$database < table_create.sql

