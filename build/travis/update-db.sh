#! /bin/bash

set -x

cd ../phase3

php maintenance/update.php --quick
