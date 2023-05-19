#!/bin/bash
# These PHPStan tests depend on MediaWiki core and its composer dependencies for autoloading. We generally want the
# PHPStan tests to run whenever `composer test` is executed, but some environments expect `composer test` not to rely on
# anything outside of Wikibase.git, e.g. CI on release branches (see T333454). In such cases we simply skip running
# PHPStan, since we only care about the test result for new patches against the master branch.

SCRIPT_DIR=$(dirname "$0")
WB_DIR="$SCRIPT_DIR/.."
CORE_DIR="$WB_DIR/../.."
CORE_VENDOR_DIR="$CORE_DIR/vendor"

if [[ -d $CORE_VENDOR_DIR && -n "$(ls -A $CORE_VENDOR_DIR)" ]];
then
    $WB_DIR/vendor/bin/phpstan analyze -a $CORE_DIR/tests/phpunit/bootstrap.php
else
	echo "Cannot run PHPStan because MediaWiki dependencies are not installed."
fi
