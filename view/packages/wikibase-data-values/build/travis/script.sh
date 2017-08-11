#! /bin/bash

set -x

if [[ $RUNJOB == qunit ]]; then
	cd lib/TestRunner
	phantomjs runTests.phantom.js ../../tests/runTests.html
	exit $?
fi
