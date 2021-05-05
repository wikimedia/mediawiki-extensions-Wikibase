#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ..

mkdir mediawiki
wget -O- https://github.com/wikimedia/mediawiki/archive/$MW_BRANCH.tar.gz | tar -zxf - -C mediawiki --strip-components 1

cd mediawiki/extensions

if [ "$WB" != "repo" ]; then
	git clone -b $MW_BRANCH https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto.git --depth 1
fi
git clone -b $MW_BRANCH https://gerrit.wikimedia.org/r/mediawiki/extensions/cldr --depth 1

cp -rT $originalDirectory Wikibase

cd ..

cp $originalDirectory/build/ci-scripts/composer.local.json composer.local.json

composer install

# Try composer install again... this tends to fail from time to time
if [ $? -gt 0 ]; then
	composer install
fi

mysql -e 'create database test_db_wiki;' -uroot -proot -h"127.0.0.1"
php maintenance/install.php \
    --dbtype $DBTYPE \
    --dbserver 127.0.0.1 \
    --dbuser root \
    --dbpass root \
    --dbpath $(pwd) \
    --pass shie3Ekutaiy5Giebuwi \
    TestWiki admin
