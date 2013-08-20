# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item description tests

Feature: Edit description

  Background:
    Given I am on an item page

  Scenario: Description UI has all required elements
    Then Original description should be displayed
      And Description edit button should be there
      And Description cancel button should not be there

  Scenario: Click edit button
    When I click the description edit button
    Then Description input element should be there
      And Description input element should contain original description
      And Description cancel button should be there

  Scenario: Modify the description
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
    Then Description save button should be there
      And Description cancel button should be there
      And Description edit button should not be there

  Scenario: Description cancel
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I click the description cancel button
    Then Original description should be displayed
      And Description edit button should be there
      And Description cancel button should not be there

  Scenario: Description cancel with ESCAPE
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I press the ESC key in the description input field
    Then Original description should be displayed
      And Description edit button should be there
      And Description cancel button should not be there

  @save_description
  Scenario: Description save
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I click the description save button
    Then MODIFIED DESCRIPTION should be displayed as description
    When I reload the page
    Then MODIFIED DESCRIPTION should be displayed as description

  @save_description
  Scenario: Description save with RETURN
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I press the RETURN key in the description input field
    Then MODIFIED DESCRIPTION should be displayed as description
    When I reload the page
    Then MODIFIED DESCRIPTION should be displayed as description

  @save_description
  Scenario: Description with unnormalized value
    When I click the description edit button
      And I enter    bla   bla    as description
      And I click the description save button
    Then bla bla should be displayed as description

  @save_description
  Scenario: Description with "0" as value
    When I click the description edit button
      And I enter 0 as description
      And I click the description save button
    Then 0 should be displayed as description

  @save_description
  Scenario: Description with a too long value
    When I click the description edit button
      And I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong as description
      And I click the description save button
    Then An error message should be displayed
