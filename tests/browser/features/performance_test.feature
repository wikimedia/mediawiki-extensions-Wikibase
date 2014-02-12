# Wikidata item tests
#
# License:: GNU GPL v2+
#
# feature the functionality of a non existing item page

Feature: High performance

Scenario Outline: Showing a huge page
	Given I am on page <pagename>
  Then get loading time of that page

Examples:
	| pagename |
	| Italy |
