#! /bin/bash

set -x

PHPVERSION=`phpenv version-name`

originalDirectory=$(pwd)

cd ..

MW_BRANCH=master
if [[ "$TRAVIS_BRANCH" =~ ^wmf/[0-9]+.* ]] || [[ "$TRAVIS_BRANCH" =~ ^REL[0-9]+_[0-9]+ ]]; then
	MW_BRANCH="$TRAVIS_BRANCH"
fi

mkdir phase3
wget -O- https://github.com/wikimedia/mediawiki/archive/$MW_BRANCH.tar.gz | tar -zxf - -C phase3 --strip-components 1

cd phase3/extensions

if [ "$WB" != "repo" ]; then
	git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto.git --depth 1
fi
git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/cldr --depth 1

cp -rT $originalDirectory Wikibase

cd ..

cp $originalDirectory/build/ci-scripts/composer.local.json composer.local.json

composer self-update --1 # TODO: Allow using Composer version 2: T266455
composer install

# Try composer install again... this tends to fail from time to time
if [ $? -gt 0 ]; then
	composer install
fi

mysql -e 'create database its_a_mw;' -uroot -proot -h"127.0.0.1"
php maintenance/install.php \
    --dbtype $DBTYPE \
    --dbserver 127.0.0.1 \
    --dbuser root \
    --dbpass root \
    --dbpath $(pwd) \
    --pass shie3Ekutaiy5Giebuwi \
    TravisWiki admin
