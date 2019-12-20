#!/bin/bash

# Run Doxygen in docker using the same docker image as CI
# The 'latest' image ius used. A changelog can be found at https://gerrit.wikimedia.org/g/integration/config/+/master/dockerfiles/doxygen/changelog
# The 'latest' image tag may differ slightly from the version being used in CI for Wikibase at any given time.
#
# This script can be run using a composer script. `composer doxygen-docker`
# The output of the docs build can be found in docs/php

docker run --rm -v /"$(pwd)"://src --entrypoint=doxygen --workdir=//src docker-registry.wikimedia.org/releng/doxygen:latest
