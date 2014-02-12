# Wikidata item tests
#
# License:: GNU GPL v2+
#
# feature the functionality of a non existing item page

Feature: High performance

Scenario Outline: Creating a huge entity
	Given Entity <pagename> exists

Examples:
	| pagename |
	| Italy |

Scenario Outline: Loading a huge entity
  Then get loading time of it's page

Examples:
	| pagename |
	| Italy |
