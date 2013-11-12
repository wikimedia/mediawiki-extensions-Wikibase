# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for statements tests

@wikidata.beta.wmflabs.org
Feature: Creating statements

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Statement UI has all required elements
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Entity selector input element should not be there
      And Statement value input element should not be there

  @ui_only
  Scenario: Click the add button
    When I click the statement add button
    Then Statement add button should be disabled
      And Statement cancel button should be there
      And Statement save button should be disabled
      And Statement help field should be there
      And Entity selector input element should be there
      And Statement value input element should not be there

  @ui_only
  Scenario Outline: Cancel statement
    When I click the statement add button
      And I <cancel>
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Entity selector input element should not be there
      And Statement value input element should not be there

  Examples:
    | cancel |
    | click the statement cancel button |
    | press the ESC key in the entity selector input field |
