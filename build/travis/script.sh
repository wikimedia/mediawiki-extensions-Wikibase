#! /bin/bash

set -x

cd ../phase3/extensions/Wikibase

if [ "$MW-$DBTYPE" == "master-mysql" ]
then
	phpunit --coverage-clover ../../extensions/Wikibase/build/logs/clover.xml
else
	phpunit
fi