#! /bin/bash

set -x

cd ../phase3/tests/phpunit

php phpunit.php --group Wikibase
