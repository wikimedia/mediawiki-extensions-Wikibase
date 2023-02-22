#!/bin/bash

# This is a temporary hack which is needed because some of our CI runs in Node.js v14 (browser tests), and some in v16.
# The packages below are incompatible with Node.js v16 and error during the build process.

if [[ $(node --version) == v16* ]]; then
	npm uninstall @wdio/sync fibers
fi
