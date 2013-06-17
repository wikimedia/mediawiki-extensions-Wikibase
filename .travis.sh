#! /bin/bash

set -x

cd ..

git clone https://gerrit.wikimedia.org/r/p/mediawiki/core.git phase3 --depth 1

cd phase3

mysql -e 'create database its_a_mw;'
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin

cd extensions
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Diff.git
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/DataValues.git
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/WikibaseDataModel.git
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Wikibase.git

cd ..
echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
echo 'ini_set("display_errors", 1);' >> LocalSettings.php
echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php

if [ "$1" == "repo" ]
then
	echo "define( 'WB_EXPERIMENTAL_FEATURES', true );" >> LocalSettings.php
	echo 'require_once __DIR__ . "/extensions/Wikibase/repo/Wikibase.php";' >> LocalSettings.php
	echo 'require_once __DIR__ . "/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
else
	echo 'require_once __DIR__ . "/extensions/Wikibase/client/WikibaseClient.php";' >> LocalSettings.php
fi

php maintenance/update.php --quick
