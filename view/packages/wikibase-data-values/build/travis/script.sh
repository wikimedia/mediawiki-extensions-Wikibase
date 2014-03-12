#! /bin/bash

set -x

cd lib/TestRunner

phantomjs runTests.phantom.js ../../tests/runTests.html
