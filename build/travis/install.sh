#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ..

wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
tar -zxf $MW.tar.gz
mv mediawiki-$MW phase3

cd phase3
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
mv phpunit.phar tests/phpunit/

git checkout $MW

mysql -e 'create database its_a_mw;'
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin

cd extensions

git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto.git --depth 1

cp -r $originalDirectory Wikibase

cd Wikibase

composer self-update
composer install --prefer-source
