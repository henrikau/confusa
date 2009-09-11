#!/bin/bash
target=ConfusaDoc/
file_mask="*.php"
ignore_files="*.tpl.*"
doc_format="HTML:frames:phphtmllib"

phpdoc -o $doc_format -i $ignore_files -f $file_mask -t $target
