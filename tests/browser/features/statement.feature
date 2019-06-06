# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
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
      And Claim entity selector input element should not be there
      And Claim value input element should not be there
      And Rank selector for claim 1 in group 1 should not be there
      And Snaktype selector for claim 1 in group 1 should not be there

  @ui_only
  Scenario: Click the add button
    When I click the statement add button
    Then Statement add button should be there
      And Statement cancel button should be there
      And Statement save button should be disabled
      And Statement help field should be there
      And Claim entity selector input element should be there
      And Claim value input element should not be there
      And Rank selector for claim 1 in group 1 should be there
      And Snaktype selector for claim 1 in group 1 should not be there

  @ui_only
  Scenario Outline: Cancel statement
    When I click the statement add button
      And I close the entity selector popup if present
      And I <cancel>
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Claim entity selector input element should not be there
      And Claim value input element should not be there
      And Rank selector for claim 1 in group 1 should not be there
      And Snaktype selector for claim 1 in group 1 should not be there

  Examples:
    | cancel |
    | click the statement cancel button |
    | press the ESC key in the claim entity selector input field |

# T221104
#  @ui_only
#  Scenario: Select a property
#    Given I have the following properties with datatype:
#      | stringprop | string |
#    When I click the statement add button
#      And I select the claim property stringprop
#    Then Statement add button should be there
#      And Statement cancel button should be there
#      And Statement save button should be disabled
#      And Claim entity selector input element should be there
#      And Claim value input element should be there
#      And Rank selector for claim 1 in group 1 should be there
#      And Snaktype selector for claim 1 in group 1 should be there
#
#  @smoke @ui_only
#  Scenario: Select a property and enter a statement value
#    Given I have the following properties with datatype:
#      | stringprop | string |
#    When I click the statement add button
#      And I select the claim property stringprop
#      And I enter something in the claim value input field
#    Then Statement add button should be there
#      And Statement cancel button should be there
#      And Statement save button should be there
#      And Claim entity selector input element should be there
#      And Claim value input element should be there
#      And Rank selector for claim 1 in group 1 should be there
#      And Snaktype selector for claim 1 in group 1 should be there
#
#  @ui_only
#  Scenario Outline: Cancel statement after selecting a property
#    Given I have the following properties with datatype:
#      | stringprop | string |
#    When I click the statement add button
#      And I select the claim property stringprop
#      And I enter something in the claim value input field
#      And I <cancel>
#    Then Statement add button should be there
#      And Statement cancel button should not be there
#      And Statement save button should not be there
#      And Claim entity selector input element should not be there
#      And Claim value input element should not be there
#      And Rank selector for claim 1 in group 1 should not be there
#      And Snaktype selector for claim 1 in group 1 should not be there
#
#  Examples:
#    | cancel |
#    | click the statement cancel button |
#    | press the ESC key in the claim value input field |
#
#  @ui_only
#  Scenario: Select a property, enter a statement value and clear the property
#    Given I have the following properties with datatype:
#      | stringprop | string |
#    When I click the statement add button
#      And I select the claim property stringprop
#      And I enter something in the claim value input field
#      And I enter invalid in the claim property input field
#    Then Statement add button should be there
#      And Statement cancel button should be there
#      And Statement save button should be disabled
#      And Claim entity selector input element should be there
#      And Claim value input element should not be there
#      And Rank selector for claim 1 in group 1 should be there
#      And Snaktype selector for claim 1 in group 1 should not be there
