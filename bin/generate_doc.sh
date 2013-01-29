#!/bin/bash
path="$(pwd)/$0"
path="$(dirname $path)"
path="$(dirname $path)"
pushd "$path" > /dev/null

if !  which phpdoc > /dev/null ; then
    echo "phpdoc not installed, cannot run script, aborting" > /dev/stderr
    echo "phpdoc is normally found in PhpDocumentor found in the PEAR library" > /dev/stderr
    exit 1
fi

target=ConfusaDoc/
file_mask="*.php"
ignore_files="*.tpl.*"
doc_format="HTML:frames:phphtmllib"

phpdoc -o $doc_format -i $ignore_files -f $file_mask -t $target
