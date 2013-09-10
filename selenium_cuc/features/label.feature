# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item label tests

Feature: Edit label

  Background:
    Given I am on an item page

  @ui_only
  Scenario: Label UI has all required elements
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there

  @ui_only
  Scenario: Click edit button
    When I click the label edit button
    Then Label input element should be there
      And Label input element should contain original label
      And Label cancel button should be there

  @ui_only
  Scenario: Modify the label
    When I click the label edit button
      And I enter MODIFIED LABEL as label
    Then Label save button should be there
      And Label cancel button should be there
      And Label edit button should not be there

  @ui_only
  Scenario: Label cancel
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I click the label cancel button
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there

  @ui_only
  Scenario: Label cancel with ESCAPE
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I press the ESC key in the label input field
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there

  @save_label @modify_entity
  Scenario: Label save
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I click the label save button
    Then MODIFIED LABEL should be displayed as label
    When I reload the page
    Then MODIFIED LABEL should be displayed as label

  @save_label @modify_entity
  Scenario: Label save with RETURN
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I press the RETURN key in the label input field
    Then MODIFIED LABEL should be displayed as label
    When I reload the page
    Then MODIFIED LABEL should be displayed as label

  @save_label @modify_entity
  Scenario: Label with unnormalized value
    When I click the label edit button
      And I enter    bla   bla    as label
      And I click the label save button
    Then bla bla should be displayed as label

  @save_label @modify_entity
  Scenario: Label with "0" as value
    When I click the label edit button
      And I enter 0 as label
      And I click the label save button
    Then 0 should be displayed as label

  @save_label
  Scenario: Label with a too long value
    When I click the label edit button
      And I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong as label
      And I click the label save button
    Then An error message should be displayed
