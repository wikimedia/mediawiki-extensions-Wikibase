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
      And Modified alias input field should be there
      And New alias input field should be there

  Scenario: Aliases cancel
    When I click the aliases add button
      And I enter alias123 as new alias
      And I click the aliases cancel button
    Then Aliases add button should be there
      And Aliases save button should not be there
      And Aliases edit button should not be there
      And Aliases cancel button should not be there
      And New alias input field should not be there
      And Aliases list should be empty

  Scenario: Aliases cancel with ESCAPE
    When I click the aliases add button
      And I enter alias123 as new alias
      And I press the ESC key in the new alias input field
    Then Aliases add button should be there
      And Aliases save button should not be there
      And Aliases edit button should not be there
      And Aliases cancel button should not be there
      And New alias input field should not be there
      And Aliases list should be empty

  Scenario: Save alias
    When I click the aliases add button
      And I enter alias123 as new alias
      And I click the aliases save button
    Then Aliases list should not be empty
      And Aliases add button should not be there
      And Aliases cancel button should not be there
      And Aliases save button should not be there
      And Aliases edit button should be there
      And There should be 1 aliases in the list
      And List of aliases should contain alias123
    When I reload the page
    Then Aliases edit button should be there
      And There should be 1 aliases in the list
      And List of aliases should contain alias123

  Scenario: Save alias with RETURN
    When I click the aliases add button
      And I enter alias123 as new alias
      And I press the RETURN key in the new alias input field
    Then Aliases list should not be empty
      And Aliases add button should not be there
      And Aliases cancel button should not be there
      And Aliases save button should not be there
      And Aliases edit button should be there
      And There should be 1 aliases in the list
      And List of aliases should contain alias123
    When I reload the page
    Then Aliases edit button should be there
      And There should be 1 aliases in the list
      And List of aliases should contain alias123

  Scenario: Save multiple aliases
    When I click the aliases add button
      And I enter alias1,alias2,alias3 as new aliases
      And I click the aliases save button
    Then Aliases list should not be empty
      And There should be 3 aliases in the list
      And List of aliases should contain alias1,alias2,alias3

  Scenario: Remove alias
    When I click the aliases add button
      And I enter alias1,alias2 as new aliases
      And I click the aliases save button
      And I click the aliases edit button
      And I click the remove first alias button
      And I click the aliases save button
    Then List of aliases should contain alias2
      And There should be 1 aliases in the list

  Scenario: Edit aliases UI
    When I click the aliases add button
      And I enter alias123 as new alias
      And I click the aliases save button
      And I click the aliases edit button
    Then New alias input field should be there
      And First alias input field should contain alias123
      And Aliases save button should be disabled
      And Aliases cancel button should be there
      And First remove alias button should be there

  Scenario: Edit multiple aliases
    When I click the aliases add button
      And I enter alias1,alias2 as new aliases
      And I click the aliases save button
      And I click the aliases edit button
      And I enter alias3,alias4 as new aliases
      And I click the remove first alias button
      And I change the first alias to alias5
      And I click the aliases save button
    Then There should be 3 aliases in the list
      And List of aliases should contain alias3,alias4,alias5