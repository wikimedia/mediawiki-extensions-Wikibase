#!/bin/bash

build_dir=$(dirname $(php -r "echo realpath('$0'), \"\\n\";"))

php $build_dir/generateAutoload.php > /dev/null

git update-index --refresh -q
git diff-index --quiet HEAD client/autoload.php data-access/autoload.php lib/autoload.php repo/autoload.php view/autoload.php
error=$?
if [ $error -gt 0 ]
then
    echo "Autoload files out of sync. Commit updated autoload files"
    exit $error
fi
