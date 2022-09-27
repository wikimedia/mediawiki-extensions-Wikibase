#!/usr/bin/env bash

filepath="$1"

# get the first line of the file
firstLine=$(head --lines=1 "$filepath")

match_header_regexp='^\s*#\s.*$'
match_header_id_regexp='^\s*#\s.*\{#.*\}$'
# if the first line is a header that doesn't already have a header id attribute
# https://doxygen.nl/manual/markdown.html#md_header_id
if [[ "$firstLine" =~ $match_header_regexp ]] && [[ ! "$firstLine" =~ $match_header_id_regexp ]]; then
	# create anchor from filename
	anchor=$(realpath --relative-to="." "$filepath")

	# replace all occurrences of '/' with '_'
	anchor="${anchor//\//_}"

	# remove '.md' extension from the end
	anchor=${anchor%".md"}

	# TODO: should the anchor replace all non-alphanumeric characters with an underscore?

	# add the anchor to the first line
	firstLine="$firstLine {#$anchor}"
fi

# print the (possibly modified) first line
echo "$firstLine"

# print the rest of the file
tail --lines=+2 "$filepath"
