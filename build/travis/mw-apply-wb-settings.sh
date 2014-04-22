#!/bin/bash

set -x

cd ../phase3

function apply_client_settings {
  echo '$wgEnableWikibaseRepo = false;' >> LocalSettings.php
  echo '$wgEnableWikibaseClient = true;' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Wikibase/client/WikibaseClient.php";' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Wikibase/client/ExampleSettings.php";' >> LocalSettings.php
  echo '$wgWBClientSettings["siteGlobalID"] = "enwiki";' >> LocalSettings.php
}

function apply_repo_settings {
  echo '$wgEnableWikibaseRepo = true;' >> LocalSettings.php
  echo '$wgEnableWikibaseClient = false;' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Wikibase/repo/Wikibase.php";' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
}

function apply_common_settings {
  echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
  echo 'ini_set("display_errors", 1);' >> LocalSettings.php
  echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
  echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
  echo '$wgLanguageCode = "'$LANG'";' >> LocalSettings.php
  echo '$wgDebugLogFile = "mw-debug.log";' >> LocalSettings.php
  echo "define( 'WB_EXPERIMENTAL_FEATURES', 1 );" >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Scribunto/Scribunto.php";' >> LocalSettings.php
}

apply_common_settings

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
