#! /bin/bash

set -ex

cd ../mediawiki/tests/phpunit

php phpunit.php --group Wikibase
