#!/bin/bash
# These PHPStan tests depend on MediaWiki core and its composer dependencies for autoloading. We generally want the
# PHPStan tests to run whenever `composer test` is executed, but some environments expect `composer test` not to rely on
# anything outside of Wikibase.git, e.g. CI on release branches (see T333454). In such cases we simply skip running
# PHPStan, since we only care about the test result for new patches against the master branch.

SCRIPT_DIR=$(dirname "$0")
WB_DIR="$SCRIPT_DIR/.."
CORE_DIR="$WB_DIR/../.."
CORE_VENDOR_DIR="$CORE_DIR/vendor"
LOCAL_SETTINGS_FILE="$CORE_DIR/LocalSettings.php"

if [[ -d $CORE_VENDOR_DIR && -n "$(ls -A $CORE_VENDOR_DIR)" ]];
then
	CREATED_LOCAL_SETTINGS=false
	if [ ! -f $LOCAL_SETTINGS_FILE ]; then
		# As of T227900, tests/phpunit/bootstrap.php which we use for class loading here, requires LocalSettings.php to
		# exist. Since we're not actually running PHPUnit tests here, we just create a minimal LocalSettings.php loading
		# Wikibase repo.
		echo '<?php
		$wgServer = "not-actually-needed-here";
		wfLoadExtension( "WikibaseRepository", __DIR__ . "/extensions/Wikibase/extension-repo.json" );
		require_once __DIR__ . "/extensions/Wikibase/repo/config/Wikibase.ci.php";' > $LOCAL_SETTINGS_FILE
		CREATED_LOCAL_SETTINGS=true
	fi

	$WB_DIR/vendor/bin/phpstan analyze -a $CORE_DIR/tests/phpunit/bootstrap.php

	# Delete LocalSettings.php if we created it. Following test code may depend on it not being there.
	if [ "$CREATED_LOCAL_SETTINGS" = true ]; then
		rm $LOCAL_SETTINGS_FILE
	fi
else
	echo "Cannot run PHPStan because MediaWiki dependencies are not installed."
fi
