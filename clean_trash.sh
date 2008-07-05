#!/bin/bash
find . -name "*~" -exec rm -f {} \;
echo "all files deemed unworthy, expunged!"
