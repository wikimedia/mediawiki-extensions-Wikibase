#!/bin/bash

set -x

cd ../phase3

function apply_client_settings {
  echo '$wgEnableWikibaseClient = true;' >> LocalSettings.php
  echo '$wgWBClientSettings["siteGlobalID"] = "enwiki";' >> LocalSettings.php
  echo 'require_once __DIR__ . "/extensions/Scribunto/Scribunto.php";' >> LocalSettings.php
  echo 'wfLoadExtension( "WikibaseClient", "$IP/extensions/Wikibase/client/extension.json" );' >> LocalSettings.php
  # Use example config for testing
  echo 'require_once __DIR__ . "/extensions/Wikibase/client/config/WikibaseClient.example.php";' >> LocalSettings.php
  # TODO make this unncessary. Include hack to make testing work with the current code
  echo 'require_once __DIR__ . "/extensions/Wikibase/client/config/WikibaseClient.jenkins.php";' >> LocalSettings.php
}

function apply_repo_settings {
  echo '$wgEnableWikibaseRepo = true;' >> LocalSettings.php
  echo 'wfLoadExtension( "WikibaseRepo", "$IP/extensions/Wikibase/repo/extension.json" );' >> LocalSettings.php
  # Use example config for testing
  echo 'require_once __DIR__ . "/extensions/Wikibase/repo/config/Wikibase.example.php";' >> LocalSettings.php
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
  echo 'wfLoadExtension( "WikibaseLib", "$IP/extensions/Wikibase/lib/extension.json" );' >> LocalSettings.php
  echo 'wfLoadExtension( "Wikibase View", "$IP/extensions/Wikibase/view/extension.json" );' >> LocalSettings.php
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
