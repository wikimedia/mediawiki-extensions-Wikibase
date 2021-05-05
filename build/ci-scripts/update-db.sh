#! /bin/bash

set -x

cd ../mediawiki

php maintenance/update.php --quick
