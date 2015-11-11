#!/bin/bash -xe

function usage {
  echo "usage: $0 -r <repo|client> -e <true|false> -b <true|false>"
  echo "       -r specify if the settings are for repo or client"
  echo "       -b specify if the settings are for a build or not"
  exit 1
}

while getopts r:e:b: opt
do
   case $opt in
       r) REPO="$OPTARG";;
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
    echo 'require_once __DIR__ . "/extensions/Wikibase/Wikibase.php";' >> LocalSettings.php
  fi
}

function apply_repo_settings {
  echo '$wgEnableWikibaseRepo = true;' >> LocalSettings.php
  echo '$wgEnableWikibaseClient = true;' >> LocalSettings.php
  # done by jenkins job: $wgWikimediaJenkinsCI = true
  echo '$wmgUseWikibaseRepo = true;' >> LocalSettings.php
  echo '$wmgUseWikibaseClient = true;' >> LocalSettings.php
  if [ $BUILD = true ]
  then
    echo 'require_once __DIR__ . "/extensions/Wikidata/Wikidata.php";' >> LocalSettings.php
    echo 'require_once __DIR__ . "/extensions/Wikidata/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
    echo 'require_once __DIR__ . "/extensions/Wikidata/extensions/Wikibase/client/ExampleSettings.php";' >> LocalSettings.php
  else
    echo 'require_once __DIR__ . "/extensions/Wikibase/Wikibase.php";' >> LocalSettings.php
  fi
}

cd $WORKSPACE/src

if [ "$(tail -n1 LocalSettings.php)" = "?>" ]
then
  PHPTAGS=true
fi
if [ -v PHPTAGS ]
then
  echo '<?php' >> LocalSettings.php
fi

if [ "$REPO" = "repo" ]
then
  apply_repo_settings
elif [ "$REPO" = "client" ]
then
  apply_client_settings
else
  usage
fi

if [ -v PHPTAGS ]
then
  echo '?>' >> LocalSettings.php
fi
