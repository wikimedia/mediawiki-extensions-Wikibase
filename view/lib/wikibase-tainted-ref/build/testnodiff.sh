#!/usr/bin/env bash
GIT_BUILD_DIR="$1"
TEMP_BUILD_DIR="$2"
find "$GIT_BUILD_DIR" -type f -exec realpath -z --relative-to="$GIT_BUILD_DIR" {} \; | \
xargs -0 -I % diff -q "$GIT_BUILD_DIR/%" "$TEMP_BUILD_DIR/%"
