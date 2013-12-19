#! /bin/bash

set -x

cd ../phase3/tests/phpunit

if [ "$TYPE" == "coverage" ]
then
	php phpunit.php --group Wikibase --coverage-clover ../../extensions/Wikibase/build/logs/clover.xml
else
	php phpunit.php --group Wikibase
fi
