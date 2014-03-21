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

  @ui_only @repo_login
  Scenario: Select a property
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
    Then Statement add button should be disabled
      And Statement cancel button should be there
      And Statement save button should be disabled
      And Entity selector input element should be there
      And Statement value input element should be there

  @ui_only @repo_login @smoke
  Scenario: Select a property and enter a statement value
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
      And I enter something as string statement value
    Then Statement add button should be disabled
      And Statement cancel button should be there
      And Statement save button should be there
      And Entity selector input element should be there
      And Statement value input element should be there

  @ui_only @repo_login
  Scenario Outline: Cancel statement after selecting a property
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
      And I enter something as string statement value
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
    | press the ESC key in the statement value input field |

  @ui_only @repo_login
  Scenario: Select a property, enter a statement value and clear the property
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
      And I enter something as string statement value
      And I enter invalid in the property input field
    Then Statement add button should be disabled
      And Statement cancel button should be there
      And Statement save button should be disabled
      And Entity selector input element should be there
      And Statement value input element should not be there

  @ui_only @repo_login
  Scenario: Select a property, use entity selector
    Given There are properties with the following handles and datatypes:
      | itemprop | wikibase-item |
    When I click the statement add button
      And I select the property itemprop
			And I press the q key in the second entity selector input field
			And I press the ARROWDOWN key in the second entity selector input field
			And I press the ARROWDOWN key in the second entity selector input field
			And I press the ENTER key in the second entity selector input field
			And I memorize the value of the second entity selector input field
			And I press the ENTER key in the second entity selector input field
    Then Statement item value of claim 1 in group 1 should be what I memorized
