#! /bin/bash

set -x

if [[ $RUNJOB == eslint ]]; then
	npm install eslint
        npm install --save eslint-config-wikimedia
	./node_modules/.bin/eslint .
	exit $?
fi

if [[ $RUNJOB == qunit ]]; then
	cd lib/TestRunner
	phantomjs runTests.phantom.js ../../tests/runTests.html
	exit $?
fi
