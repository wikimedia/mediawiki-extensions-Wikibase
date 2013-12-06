# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for ordering statements within the same property group

@wikidata.beta.wmflabs.org
Feature: Ordering statements

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only @repo_login
  Scenario: Statement UI has all required elements
    Given There are properties with the following handles and datatypes:
      | stringprop | string |
    Given There are statements with the following properties and values:
      | stringprop | abc |
      | stringprop | def |
      | stringprop | ghi |
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Entity selector input element should not be there
      And Statement value input element should not be there
