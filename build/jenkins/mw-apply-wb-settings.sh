#!/bin/bash -xeu

function usage {
  echo "usage: $0 -r <repo|client> -b <true|false>"
  echo "       -r specify if the settings are for repo or client"
  echo "       -b specify if the settings are for a build or not"
  exit 1
}

while getopts r:b: opt
do
   case $opt in
       r) REPO="$OPTARG";;
       b) BUILD=$OPTARG;;
   esac
done

if [[ "${BUILD:-}" == true ]]; then
  echo "-b true is not supported by this script anymore."
  exit 1
fi

if [ ! -v WORKSPACE ]; then
  echo "\$WORKSPACE environment variable must be set."
  exit 1
fi

function apply_client_settings {
  echo "Applying client settings"

  mv LocalSettings.php LocalSettings-$$.php
  cat <<'PHP' > LocalSettings.php
<?php
// mw-apply-wb-settings.php:
$wgEnableWikibaseRepo = false;
$wgEnableWikibaseClient = true;
// $wgWikimediaJenkinsCI is usually set by Jenkins/Quibble
$wgWikimediaJenkinsCI = true;
$wmgUseWikibaseRepo = false;
$wmgUseWikibaseClient = true;
PHP
  echo -n '?>' >> LocalSettings.php
  cat LocalSettings-$$.php >> LocalSettings.php
  rm LocalSettings-$$.php
}

function apply_repo_settings {
  echo "Applying repo settings"

  mv LocalSettings.php LocalSettings-$$.php
  cat <<'PHP' > LocalSettings.php
<?php
// mw-apply-wb-settings.php:
$wgEnableWikibaseRepo = true;
$wgEnableWikibaseClient = true;
// $wgWikimediaJenkinsCI is usually set by Jenkins/Quibble
$wgWikimediaJenkinsCI = true;
$wmgUseWikibaseRepo = true;
$wmgUseWikibaseClient = true;
PHP
  echo -n '?>' >> LocalSettings.php
  cat LocalSettings-$$.php >> LocalSettings.php
  rm LocalSettings-$$.php
}

cd $WORKSPACE/src
if [ "${REPO:-}" = "repo" ]
then
  apply_repo_settings
elif [ "${REPO:-}" = "client" ]
then
  apply_client_settings
else
  usage
fi
