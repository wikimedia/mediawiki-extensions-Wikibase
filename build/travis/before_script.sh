#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ..

wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
tar -zxf $MW.tar.gz
mv mediawiki-core-$MW phase3

cd phase3

git checkout $MW

mysql -e 'create database its_a_mw;'
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin

cd extensions

git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto.git --depth 1

cp -r $originalDirectory Wikibase

cd Wikibase
composer install --prefer-source

cd ../..

echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
echo 'ini_set("display_errors", 1);' >> LocalSettings.php
echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
echo '$wgLanguageCode = "'$LANG'";' >> LocalSettings.php

echo "define( 'WB_EXPERIMENTAL_FEATURES', true );" >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/Wikibase/repo/Wikibase.php";' >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/Wikibase/client/WikibaseClient.php";' >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/Scribunto/Scribunto.php";' >> LocalSettings.php
echo '$wgWBClientSettings["siteGlobalID"] = "enwiki";' >> LocalSettings.php

php maintenance/update.php --quick
