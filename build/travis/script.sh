#! /bin/bash

set -ex

cd ../phase3/tests/phpunit

php phpunit.php --group Wikibase,Purtle
