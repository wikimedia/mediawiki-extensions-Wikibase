#! /bin/bash

set -x

if [[ $RUNJOB == jshint ]]; then
	npm install jshint
	jshint src/ lib/ tests/
	exit $?
fi
