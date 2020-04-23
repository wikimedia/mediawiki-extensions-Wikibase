#!/bin/bash
# Runs phpcs across all files that have been modified according to git.

files=$(git ls-files -om --exclude-standard)
phpcs -p -s $files
