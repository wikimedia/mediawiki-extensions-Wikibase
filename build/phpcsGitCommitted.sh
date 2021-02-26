#!/bin/bash
# Runs phpcs across all PHP files that were modified in the last Git commit.

phpcs -p -s --file-list=<(
    git diff-tree \
        --no-commit-id \
        --name-only \
        -r \
        --diff-filter=AM \
        @ \
        '*.php'
    echo /dev/null # prevent empty file list
)
