#! /bin/bash

set -ex

cd ../mediawiki

composer phpunit -- --group Wikibase
