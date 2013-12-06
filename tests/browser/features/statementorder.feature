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
      | stringprop1 | string |
      | stringprop2 | string |
      | stringprop3 | string |
      | stringprop4 | string |
      | stringprop5 | string |
      | stringprop6 | string |
      | stringprop7 | string |
      | stringprop8 | string |
      | stringprop9 | string |
    Given There are statements with the following properties and values:
      | stringprop1 | |
      | stringprop1 | |
      | stringprop1 | |
      | stringprop1 | |
      | stringprop1 | |
      | stringprop2 | |
      | stringprop2 | |
      | stringprop2 | |
      | stringprop3 | |
      | stringprop3 | |
      | stringprop3 | |
      | stringprop3 | |
      | stringprop3 | |
      | stringprop4 | |
      | stringprop4 | |
      | stringprop4 | |
      | stringprop5 | |
      | stringprop5 | |
      | stringprop5 | |
      | stringprop5 | |
      | stringprop5 | |
      | stringprop6 | |
      | stringprop6 | |
      | stringprop6 | |
      | stringprop7 | |
      | stringprop7 | |
      | stringprop7 | |
      | stringprop7 | |
      | stringprop7 | |
      | stringprop8 | |
      | stringprop8 | |
      | stringprop9 | |
      | stringprop9 | |
      | stringprop9 | |
      | stringprop9 | |
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Entity selector input element should not be there
      And Statement value input element should not be there
