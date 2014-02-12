# Wikidata item tests
#
# License:: GNU GPL v2+
#
# feature the functionality of a non existing item page

Feature: High performance

Scenario Outline: Showing a huge page
	Given Entity <pagename> exists
  Then get loading time of it's page

Examples:
	| pagename |
	| Italy |
