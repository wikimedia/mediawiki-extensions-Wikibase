#! /bin/bash

set -ex

cd ../mediawiki

composer phpunit:entrypoint -- --group Wikibase
