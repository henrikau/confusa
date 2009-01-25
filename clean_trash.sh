#!/bin/bash
# *very* simple script for removing backups
#
# Henrik Austad, 2008,2009
# Uninett Sigma A/S

echo -ne "starting purging of files . . . "
find . -name "*~" -exec rm -f {} \;
echo " done"
