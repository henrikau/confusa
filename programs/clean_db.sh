#!/bin/bash
# Program intended as cron-job for cleaning the database in a periodic interval
#
# Author: Henrik Austad <henrik.austad@uninett.no>
#

# Jump to directory where file resides (simplifies the include-process etc)
base=`dirname $0`
pushd $base >/dev/null

# Include libraries
if [ -z ../lib/bash/config_lib.sh ]; then
    echo "Cannot find config-library. Aborting."
    exit 127
fi
. ../lib/bash/config_lib.sh

if [ -z ../lib/bash/db_lib.sh ]; then
    echo "Cannot find db-library. Aborting."
    exit 127
fi
. ../lib/bash/db_lib.sh

csr_timeout=`get_config_array "csr_default_timeout"`
csr_timeout_value=`echo $csr_timeout | cut -d "," -f 1`
csr_timeout_unit=`echo $csr_timeout | cut -d "," -f 2 | cut -d "'" -f 2`

csr_cache="DELETE FROM csr_cache WHERE current_timestamp() > timestampadd($csr_timeout_unit, $csr_timeout_value, uploaded_date)";
cert_cache="DELETE FROM cert_cache WHERE valid_untill < current_timestamp()"

run_query "$csr_cache"  || echo "could not clean csr_cache"
run_query "$cert_cache" || echo "could not clean cert_cache"
