#!/bin/bash
# Runs phpcs across all PHP files that have been modified according to git.

phpcs -p -s --file-list=<(
    # list unstaged modified files
    git ls-files \
        --modified \
        --others \
        --exclude-standard \
        '*.php'

    # list staged modified files
    git diff --name-only --cached

    # prevent empty file list
    echo /dev/null
)
