# Wikidata item tests
#
# License:: GNU GPL v2+
#
# feature the functionality of a non existing item page

@wikidata.beta.wmflabs.org
Feature: High performance

Scenario: Showing a huge page
  Given I am on Italy's page
  Then check if this page is fast
