# Wikidata UI tests
#
# Author:: Thiemo Mättig (thiemo.maettig@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for set label special page tests

@wikidata.beta.wmflabs.org
Feature: Set label special page

#  Background:
#    Given JavaScript is disabled

  @ui_only
  Scenario: Set label special page has all required elements
    Given I am on the set label special page
    Then ID input field should be there
    And Language input field should be there
    And Label input field should be there
    And Set label button should be there

  @ui_only
  Scenario: Logged in user does not get warning
    Given I am logged in to the repo
    And I am on the set label special page
    Then Anonymous edit warning should not be there

  @ui_only
  Scenario: Anonymous user get warning
    Given I am not logged in to the repo
    And I am on the set label special page
    Then Anonymous edit warning should be there

  @ui_only
  Scenario: Add a label
  Given I have an item with empty label and description
  And I am on the set label special page
  And I enter the item ID into the ID input field
  And I enter en into the language input field
  And I enter Something into the label input field
  And I press the set label button
  And I am on the page of the item to test
  Then Something should be displayed as label

  @ui_only
  Scenario: Edit an existing label
  Given I have an item to test
  And I am on the set label special page
  And I enter the item ID into the ID input field
  And I enter en into the language input field
  And I enter Something into the label input field
  And I press the set label button
  And I am on the page of the item to test
  Then Something should be displayed as label

#  @ui_only
#  Scenario: Editing in an invalid language fails

#  @ui_only
#  Scenario: Editing in an unknown id fails
