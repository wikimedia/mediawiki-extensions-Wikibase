#! /bin/bash

set -x

cd ../phase3/extensions/DataValuesJavascript

if [ "$MW-$DBTYPE" == "master-mysql" ]
then
	phpunit --coverage-clover ../../extensions/DataValuesJavascript/build/logs/clover.xml
else
	phpunit
fi