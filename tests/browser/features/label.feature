# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item label tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Edit label

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Modify the label
    When I click the header edit button
      And I enter "MODIFIED LABEL" as label
    Then Header save button should be there
      And Header cancel button should be there
      And Header edit button should not be there

  @ui_only
  Scenario Outline: Cancel label
    When I click the header edit button
      And I enter "MODIFIED LABEL" as label
      And I <cancel>
    Then Original label should be displayed
      And Header edit button should be there
      And Header cancel button should not be there

    Examples:
      | cancel |
      | click the header cancel button |
      | press the ESC key in the label input field |

  @integration @modify_entity @save_label @smoke
  Scenario Outline: Save label
    When I click the header edit button
      And I enter "MODIFIED LABEL" as label
      And I <save>
    Then Header edit button should be there
      And "MODIFIED LABEL" should be displayed as label

    Examples:
      | save |
      | click the header save button |
      | press the RETURN key in the label input field |

# T221104
#  @modify_entity @save_label
#  Scenario Outline: Save label and reload
#    When I click the header edit button
#      And I enter "MODIFIED LABEL" as label
#      And I <save>
#      And I reload the page
#    Then Header edit button should be there
#      And "MODIFIED LABEL" should be displayed as label
#
#    Examples:
#      | save |
#      | click the header save button |
#      | press the RETURN key in the label input field |
#
#  @modify_entity @save_label
#  Scenario Outline: Label with special input
#    When I click the header edit button
#      And I enter <label> as label
#      And I click the header save button
#    Then Header edit button should be there
#      And <expected_label> should be displayed as label
#
#    Examples:
#      | label | expected_label |
#      | "0"           | "0"                    |
#      | "   normalize me  " | "normalize me" |
#      | "<script>$('body').empty();</script>" | "<script>$('body').empty();</script>" |
#      | "{{Template:blabla}}" | "{{Template:blabla}}" |
#
#  @save_label
#  Scenario: Label with a too long value
#    When I click the header edit button
#      And I enter a long string as label
#      And I click the header save button
#    Then An error message should be displayed
