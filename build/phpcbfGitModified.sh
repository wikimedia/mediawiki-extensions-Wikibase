#!/bin/bash
# Runs phpcbf across all PHP files that have been modified according to git.

phpcbf --file-list=<(
    # list staged and unstaged modified files
    git diff --name-only --diff-filter=d HEAD -- '*.php'

    # prevent empty file list
    echo /dev/null
)
