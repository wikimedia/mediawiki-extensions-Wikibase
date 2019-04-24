# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for quantity type statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Using quantity properties in statements

  Background:
    Given I have the following properties with datatype:
      | quantprop | quantity |
      And I am not logged in to the repo

# T221104
#  @ui_only @integration
#  Scenario: Quantity UI should work properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property quantprop
#      And I enter 1 in the claim value input field
#    Then Statement save button should be there
#      And Statement cancel button should be there
#      And Unit suggester should be there
#
#  @ui_only
#  Scenario Outline: Check quantity UI for invalid values
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property quantprop
#      And I enter <value> in the claim value input field
#    Then Statement save button should not be there
#      And Statement cancel button should be there
#
#  Examples:
#    | value |
#    | astring |
#    | 1:1 |
#
#  @ui_only
#  Scenario: Valueview parser state resets properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property quantprop
#      # This specific sequence triggers a bug in the valueview's internal state management
#      And I enter 1 in the claim value input field
#      And I enter 1+ in the claim value input field
#      And I enter 1 in the claim value input field
#     Then Statement save button should be there

  @modify_entity
  Scenario Outline: Quantity parser and saving should work properly
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled
    When I click the statement add button
      And I select the claim property quantprop
      And I enter <value> in the claim value input field
      And I click the statement save button
    Then Statement string value of claim 1 in group 1 should be <expected_value>
      And Statement name of group 1 should be the label of quantprop
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there

    Examples:
      | value | expected_value |
      | 1 | 1 |
      | 1+-0 | 1±0 |
      | 123+-100 | 123±100 |

  @modify_entity
  Scenario: Adding a statement of type quantity and reload page
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property quantprop
      And I enter 2.1+-0.1 in the claim value input field
      And I click the statement save button
      And I reload the page
    Then Statement string value of claim 1 in group 1 should be 2.1±0.1
      And Statement name of group 1 should be the label of quantprop
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there
