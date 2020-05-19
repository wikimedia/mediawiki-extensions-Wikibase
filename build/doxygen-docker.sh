#!/bin/bash

# Run Doxygen in docker using the same docker image as WMF CI
# The 'latest' image is used. A changelog can be found at https://gerrit.wikimedia.org/g/integration/config/+/master/dockerfiles/doxygen/changelog
# The 'latest' image tag may differ slightly from the version being used in CI for Wikibase at any given time.
#
# This script can be run using a composer script. `composer doxygen-docker`
# The output of the docs build can be found in docs/php

echo "This script deliberately hides some Doxygen errors."

# GREP #1, Ignore PHP file unknown commands as most of these are due to PHP namepsaces being used in comments
# GREP #2, Ignore PHP file comments with {@link CLASS} as this still generates the correct link in doxygen and
#          and the @link is needed for clickable links in IDEs
# GREP #3, Ignore erorrs caused by @var annotations without declaring the name of the var (only the type)
# GREP #4, Ignore doxygen trying to parse mediawiki parser functions like {{#property}} which we have in docs
docker run --rm -v $(pwd):/src --user $(id -u):$(id -g) --entrypoint=doxygen --workdir=/src docker-registry.wikimedia.org/releng/doxygen:latest \
|& grep -v -w -E -i "php:[0-9]+: warning: Found unknown command" \
|& grep -v -w -E -i "php:[0-9]+: warning: unable to resolve link to '[a-z]+' for \\\\link command" \
|& grep -v -w -E -i "php:[0-9]+: warning: documented symbol '[^']+' was not declared or defined" \
|& grep -v -w -E -i "php:[0-9]+: warning: explicit link request to '(property|statements)' could not be resolved"
