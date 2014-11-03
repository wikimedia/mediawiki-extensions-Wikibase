#! /bin/bash

set -x

if [[ $RUNJOB == jshint ]]; then
	npm install jshint
	jshint src/ lib/ tests/
	exit $?
fi

if [[ $RUNJOB == qunit ]]; then
	cd lib/TestRunner
	phantomjs runTests.phantom.js ../../tests/runTests.html
	exit $?
fi
