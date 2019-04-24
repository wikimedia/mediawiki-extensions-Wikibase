# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item description tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Empty label and description behaviour

  Background:
    Given I am on an item page with empty label and description
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

# T221104
#  @ui_only
#  Scenario: Description UI is shown correctly when description is empty
#    Then Description input element should not be there
#      And Header edit button should be there
#      And Header cancel button should not be there
#      And Header save button should not be there
#
#  @ui_only
#  Scenario: Description UI is shown correctly when description is empty
#    When I click the header edit button
#    Then Description input element should be there
#      And Description input element should be empty
#      And Header edit button should not be there
#      And Header cancel button should be there
#      And Header save button should not be there
#
#  @ui_only
#  Scenario: Description UI behaves correctly when description is empty
#    When I click the header edit button
#      And I enter "NEW DESCRIPTION" as description
#    Then Header cancel button should be there
#      And Header save button should be there
#
#  @ui_only @smoke
#  Scenario: Description UI behaves correctly when description is empty
#    When I click the header edit button
#      And I enter "NEW DESCRIPTION" as description
#      And I click the header cancel button
#    Then Header cancel button should not be there
#      And Header edit button should be there
#      And Header save button should not be there
#      And Description input element should not be there

  @ui_only
  Scenario: Label UI is shown correctly when label is empty
    Then Label input element should not be there
    And Header edit button should be there
    And Header cancel button should not be there
    And Header save button should not be there

  @ui_only
  Scenario: Label UI is shown correctly when label is empty
    When I click the header edit button
    Then Label input element should be there
      And Label input element should be empty
      And Header edit button should not be there
      And Header cancel button should be there
      And Header save button should not be there

  @ui_only
  Scenario: Label UI behaves correctly when label is empty
    When I click the header edit button
      And I enter "NEW LABEL" as label
    Then Header cancel button should be there
      And Header save button should be there

  @ui_only
  Scenario: Label UI behaves correctly when label is empty
    When I click the header edit button
      And I enter "NEW LABEL" as label
      And I click the header cancel button
    Then Header cancel button should not be there
      And Header edit button should be there
      And Header save button should not be there
      And Label input element should not be there
