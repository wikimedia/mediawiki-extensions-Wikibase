# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item description tests

Feature: Empty label and description behaviour

  Background:
    Given I am on an item page with empty label and description

  @ui_only
  Scenario: Description UI is shown correctly when description is empty
    Then Description input element should be there
      And Description input element should be empty
      And Description edit button should not be there
      And Description cancel button should not be there
      And Description save button should not be there

  @ui_only
  Scenario: Description UI behaves correctly when description is empty
    When I enter NEW DESCRIPTION as description
    Then Description cancel button should be there
      And Description save button should be there
    When I click the description cancel button
    Then Description cancel button should not be there
      And Description edit button should not be there
      And Description save button should not be there
      And Description input element should be there
      And Description input element should be empty

  @ui_only
  Scenario: Label UI is shown correctly when label is empty
    Then Label input element should be there
      And Label input element should be empty
      And Label edit button should not be there
      And Label cancel button should not be there
      And Label save button should not be there

  @ui_only
  Scenario: Label UI behaves correctly when label is empty
    When I enter NEW LABEL as label
    Then Label cancel button should be there
      And Label save button should be there
    When I click the label cancel button
    Then Label cancel button should not be there
      And Label edit button should not be there
      And Label save button should not be there
      And Label input element should be there
      And Label input element should be empty
