#! /bin/bash

set -x

pwd
cd ../phase3

php maintenance/update.php --quick
