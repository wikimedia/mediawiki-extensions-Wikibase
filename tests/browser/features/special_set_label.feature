# Wikidata UI tests
#
# Author:: Thiemo Mättig
# License:: GNU GPL v2+
#
# TODO

@wikidata.beta.wmflabs.org
Feature: Set label special page

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
#  Scenario: Editing a label succeeds
#  Scenario: Editing in an invalid language fails
#  Scenario: Editing in an unknown id fails
