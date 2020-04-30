#!/bin/bash
# Runs phpcs across all PHP files that have been modified according to git.

phpcs -p -s --file-list=<(
    git ls-files \
        --modified \
        --others \
        --exclude-standard \
        '*.php'
    echo /dev/null # prevent empty file list
)
