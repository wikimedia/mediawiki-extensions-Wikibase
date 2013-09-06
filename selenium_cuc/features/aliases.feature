# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item aliases tests

Feature: Edit aliases

  Background:
    Given I am on an item page

  Scenario: Aliases UI has all required elements
    Then Aliases UI should be there
      And Aliases add button should be there
      And Aliases edit button should not be there
      And Aliases list should be empty