#!/bin/bash
# Runs phpcbf across all PHP files that were modified in the last Git commit.

phpcbf --file-list=<(
    git diff-tree \
        --no-commit-id \
        --name-only \
        -r \
        --diff-filter=AM \
        @ \
        '*.php'
    echo /dev/null # prevent empty file list
)
