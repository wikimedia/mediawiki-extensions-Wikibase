# Wikidata item tests
#
# License:: GNU GPL v2+
#
# feature the functionality of the edit tab function

@wikidata.beta.wmflabs.org
Feature: Edit tab

Scenario: Edit tab
  Given I am on an item page
  Then the edit-tab button should not be visible


