#!/bin/bash -xe

function usage {
  echo "usage: $0 -r <repo|client> -e <true|false> -b <true|false>"
  echo "       -r specify if the settings are for repo or client"
  echo "       -e specify if experimental features should be on or off"
  echo "       -b specify if the settings are for a build or not"
  exit 1
}

while getopts r:e:b: opt
do
   case $opt in
       r) REPO="$OPTARG";;
       e) EXPERIMENTAL=$OPTARG;;
       b) BUILD=$OPTARG;;
   esac
done

function apply_client_settings {
  echo "client"
  echo '$wgEnableWikibaseRepo = false;' >> LocalSettings.php
  echo '$wgEnableWikibaseClient = true;' >> LocalSettings.php
  echo '$wmgUseWikibaseRepo = false;' >> LocalSettings.php
  echo '$wmgUseWikibaseClient = true;' >> LocalSettings.php
  if [ $BUILD = true ]
  then
    echo 'require_once __DIR__ . "/extensions/Wikidata/Wikidata.php";' >> LocalSettings.php
    echo 'require_once __DIR__ . "/extensions/Wikidata/extensions/Wikibase/client/ExampleSettings.php";' >> LocalSettings.php
  else
    echo 'require_once __DIR__ . "/extensions/Wikibase/client/WikibaseClient.php";' >> LocalSettings.php
    echo 'require_once __DIR__ . "/extensions/Wikibase/client/ExampleSettings.php";' >> LocalSettings.php
  fi
}

function apply_repo_settings {
  echo '$wgEnableWikibaseRepo = true;' >> LocalSettings.php
  echo '$wgEnableWikibaseClient = false;' >> LocalSettings.php
  echo '$wmgUseWikibaseRepo = true;' >> LocalSettings.php
  echo '$wmgUseWikibaseClient = false;' >> LocalSettings.php
  if [ $BUILD = true ]
  then
    echo 'require_once __DIR__ . "/extensions/Wikidata/Wikidata.php";' >> LocalSettings.php
    echo 'require_once __DIR__ . "/extensions/Wikidata/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
  else
    echo 'require_once __DIR__ . "/extensions/Wikibase/repo/Wikibase.php";' >> LocalSettings.php
    echo 'require_once __DIR__ . "/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
  fi
}

function apply_experimental_settings {
  echo "define( 'WB_EXPERIMENTAL_FEATURES', $EXPERIMENTAL );" >> LocalSettings.php
}

cd $WORKSPACE

echo '<?php' >> LocalSettings.php

apply_experimental_settings

if [ "$REPO" = "repo" ]
then
  apply_repo_settings
elif [ "$REPO" = "client" ]
then
  apply_client_settings
else
  usage
fi
