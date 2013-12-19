#! /bin/bash

set -x

cd ../phase3/extensions/Maps

if [ "$MW-$DBTYPE" == "master-mysql" ]
then
	phpunit --coverage-clover ../../extensions/Maps/build/logs/clover.xml
else
	phpunit
fi