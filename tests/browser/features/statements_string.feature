# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for string type statements tests

@wikidata.beta.wmflabs.org
Feature: Creating statements of type string

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @repo_login @modify_entity
  Scenario Outline: Adding a statement of type string
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
      And I enter the string <value> as statement value
      And I <save>
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Entity selector input element should not be there
      And Statement value input element should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of stringprop
      And Statement string value of claim 1 in group 1 should be <value>

  Examples:
    | value                               | save                                                    |
    | it's a string                       | press the RETURN key in the statement value input field |
    | <script>$('body').empty();</script> | click the statement save button                         |

  @repo_login @modify_entity
  Scenario: Adding a statement of type string and reload page
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
      And I enter the string it's a string as statement value
      And I click the statement save button
      And I reload the page
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Entity selector input element should not be there
      And Statement value input element should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of stringprop
      And Statement string value of claim 1 in group 1 should be it's a string

  @repo_login @modify_entity
  Scenario: Adding a statement of type string with a too long string
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    When I click the statement add button
      And I select the property stringprop
      And I enter a too long string as statement value
      And I click the statement save button
    Then An error message should be displayed
