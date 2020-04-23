#!/bin/bash
# Runs phpcbf across all files that have been modified according to git.

files=$(git ls-files -om --exclude-standard)
phpcbf $files
