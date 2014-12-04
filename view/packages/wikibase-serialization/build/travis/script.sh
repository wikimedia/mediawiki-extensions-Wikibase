#! /bin/bash

set -x

if [[ $RUNJOB == jshint ]]; then
	npm install jshint
	jshint src/ tests/
	exit $?
fi