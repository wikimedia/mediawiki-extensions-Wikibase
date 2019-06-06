# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item description tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Edit description

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

# T221104
#  @ui_only
#  Scenario: Modify the description
#    When I click the header edit button
#      And I enter "MODIFIED DESCRIPTION" as description
#    Then Header save button should be there
#      And Header cancel button should be there
#      And Header edit button should not be there

  @ui_only
  Scenario Outline: Cancel description
    When I click the header edit button
      And I enter "MODIFIED DESCRIPTION" as description
      And I <cancel>
    Then Header edit button should be there
      And Header cancel button should not be there
      And Original description should be displayed

    Examples:
      | cancel |
      | click the header cancel button |
      | press the ESC key in the description input field |

  @save_description @modify_entity
  Scenario Outline: Save description
    When I click the header edit button
      And I enter "MODIFIED DESCRIPTION" as description
      And I <save>
    Then "MODIFIED DESCRIPTION" should be displayed as description

    Examples:
     | save |
     | click the header save button |
     | press the RETURN key in the description input field |

# T221104
#  @integration @save_description @modify_entity
#  Scenario Outline: Save description and reload
#    When I click the header edit button
#      And I enter "MODIFIED DESCRIPTION" as description
#      And I <save>
#      And I reload the page
#    Then "MODIFIED DESCRIPTION" should be displayed as description
#
#    Examples:
#      | save |
#      | click the header save button |
#      | press the RETURN key in the description input field |
#
#  @save_description @modify_entity
#  Scenario Outline: Description with special input
#    When I click the header edit button
#      And I enter <description> as description
#      And I click the header save button
#    Then <expected_description> should be displayed as description
#
#    Examples:
#      | description | expected_description |
#      | "0"           | "0"                    |
#      | "   norm a lize me  " | "norm a lize me" |
#      | "<script>$('body').empty();</script>" | "<script>$('body').empty();</script>" |
#      | "{{Template:blabla}}" | "{{Template:blabla}}" |
#
#  @save_description
#  Scenario: Description with a too long value
#    When I click the header edit button
#      And I enter a long string as description
#      And I click the header save button
#    Then An error message should be displayed
