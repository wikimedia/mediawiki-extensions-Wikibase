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

  Scenario: Click add button
    When I click the aliases add button
    Then New alias input field should be there
      And Aliases add button should not be there
      And Aliases edit button should not be there
      And Aliases cancel button should be there
      And Aliases save button should be disabled
      And Aliases help field should be there

  Scenario: Type new alias
    When I click the aliases add button
    And I enter alias123 as new alias
    Then Aliases cancel button should be there
      And Aliases save button should be there

  Scenario: Aliases cancel
    When I click the aliases add button
    And I enter alias123 as new alias
    And I click the aliases cancel button
    Then Aliases add button should be there
      And Aliases save button should not be there
      And Aliases edit button should not be there
      And Aliases cancel button should not be there
      And New alias input field should not be there

  Scenario: Aliases cancel with ESCAPE
    When I click the aliases add button
    And I enter alias123 as new alias
    And I press the ESC key in the new alias input field
    Then Aliases add button should be there
    And Aliases save button should not be there
    And Aliases edit button should not be there
    And Aliases cancel button should not be there
    And New alias input field should not be there