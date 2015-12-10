#! /bin/bash

set -x

if [[ $RUNJOB == jshint ]]; then
	npm install jshint@~2.8
	jshint src/ tests/
	exit $?
fi