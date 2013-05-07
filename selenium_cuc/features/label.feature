# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item label tests

Feature: Edit label

  Background:
    Given I am on an entity page

  Scenario: Label UI has all required elements
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there
  Scenario: Click edit button
    When I click the label edit button
    Then Label input element should be there
      And Label input element should contain original label
      And Label cancel button should be there
  Scenario: Modify the label
    When I click the label edit button
      And I modify the label
    Then Label save button should be there
      And Label cancel button should be there
      And Label edit button should not be there
  Scenario: Label cancel
    When I click the label edit button
      And I modify the label
      And I click the label cancel button
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there