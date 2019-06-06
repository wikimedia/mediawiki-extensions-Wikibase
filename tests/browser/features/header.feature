# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item header tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Use header

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

# T221104
#  @ui_only
#  Scenario: Header UI has all required elements
#    Then Original label should be displayed
#      And Original description should be displayed
#      And Header edit button should be there
#      And Header cancel button should not be there
#      And Header save button should not be there
#
#  @ui_only
#  Scenario: Click edit button
#    When I click the header edit button
#    Then Header edit button should not be there
#      And Header save button should not be there
#      And Header cancel button should be there
#      And Label input element should be there
#      And Label input element should contain original label
#      And Description input element should be there
#      And Description input element should contain original description
#      And New alias input field should be there

  @ui_only
  Scenario: Modify label, description and aliases
    When I click the header edit button
      And I enter random string as label
      And I enter "MODIFIED DESCRIPTION" as description
      And I enter "alias123" as new aliases
    Then Header save button should be there
      And Header cancel button should be there
      And Header edit button should not be there
      And Modified alias input field should be there
      And New alias input field should be there

  @ui_only
  Scenario Outline: Cancel label, description and aliases
    When I click the header edit button
      And I enter random string as label
      And I enter "MODIFIED DESCRIPTION" as description
      And I enter "alias123" as new aliases
      And I <cancel>
    Then Header edit button should be there
      And Header save button should not be there
      And Header cancel button should not be there
      And Original description should be displayed
      And Original label should be displayed
      And Aliases list should be empty


  Examples:
    | cancel |
    | click the header cancel button |
    | press the ESC key in the label input field |

  @integration @modify_entity @save_description @save_aliases @save_label
  Scenario Outline: Save label, description and aliases
    When I click the header edit button
      And I enter random string as label
      And I enter "MODIFIED DESCRIPTION" as description
      And I enter "alias123" as new aliases
      And I <save>
    Then Header edit button should be there
      And Header save button should not be there
      And Header cancel button should not be there
      And random string should be displayed as label
      And "MODIFIED DESCRIPTION" should be displayed as description
      And There should be 1 aliases in the list
      And List of aliases should be "alias123"

  Examples:
    | save |
    | click the header save button |
    | press the RETURN key in the description input field |

  @bugfix @modify_entity
  Scenario: EntityTermView bugfix
    When I click the header edit button
      And I enter random string as label
      And I click the header save button
      And I reload the page
    Then random string should be displayed as label
      And random string should be displayed as English label in the EntityTermView box
