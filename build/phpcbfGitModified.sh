#!/bin/bash
# Runs phpcbf across all PHP files that have been modified according to git.

phpcbf --file-list=<(
    git ls-files \
        --modified \
        --others \
        --exclude-standard \
        '*.php'
    echo /dev/null # prevent empty file list
)
