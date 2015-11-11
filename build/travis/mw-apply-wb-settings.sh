#!/bin/bash

set -x

cd ../phase3

function apply_client_settings {
  echo '$wgEnableWikibaseClient = true;' >> LocalSettings.php
  echo '$wgWBClientSettings["siteGlobalID"] = "enwiki";' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Scribunto/Scribunto.php";' >> LocalSettings.php
}

function apply_repo_settings {
  echo '$wgEnableWikibaseRepo = true;' >> LocalSettings.php
}

function apply_common_before_settings {
  echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
  echo 'ini_set("display_errors", 1);' >> LocalSettings.php
  echo '$wgWikimediaJenkinsCI = true;' >> LocalSettings.php
  echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
  echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
  echo '$wgLanguageCode = "'$LANG'";' >> LocalSettings.php
  echo '$wgDebugLogFile = "mw-debug.log";' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/cldr/cldr.php";' >> LocalSettings.php
  echo '$wgEnableWikibaseClient = false;' >> LocalSettings.php
  echo '$wgEnableWikibaseRepo = false;' >> LocalSettings.php
}

function apply_common_after_settings {
  echo 'require_once __DIR__ . "/extensions/Wikibase/Wikibase.php";' >> LocalSettings.php
}


apply_common_before_settings

if [ "$WB" = "repo" ]
then
  apply_repo_settings
elif [ "$WB" = "client" ]
then
  apply_client_settings
else
  apply_repo_settings
  apply_client_settings
fi

apply_common_after_settings
