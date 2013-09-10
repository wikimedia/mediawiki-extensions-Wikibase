# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item sitelinks tests

Feature: Edit sitelinks

  Background:
    Given I am on an item page

  @ui_only
  Scenario: Sitelink UI has all required elements
    Then Sitelink table should be there
      And Sitelink heading should be there
      And Sitelink add button should be there
      And Sitelink counter should be there
      And Sitelink counter should show 0
      And There should be 0 sitelinks in the list

  @ui_only
  Scenario: Click add button
    When I click the sitelink add button
    Then Sitelink add button should be disabled
      And Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink help field should be there
      And Sitelink siteid input field should be there
      And Sitelink page input field should be there
      And Sitelink page input field should be disabled

  Scenario: