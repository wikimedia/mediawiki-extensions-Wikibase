# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item aliases tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Edit aliases

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Type new alias
    When I click the header edit button
      And I enter "alias123" as new aliases
    Then Header cancel button should be there
      And Header save button should be there
      And Modified alias input field should be there
      And New alias input field should be there

  @ui_only
  Scenario Outline: Cancel aliases
    When I click the header edit button
      And I enter "alias123" as new aliases
      And I <cancel>
    Then Header edit button should be there
      And Header cancel button should not be there
      And New alias input field should not be there
      And Header save button should not be there
      And Aliases list should be empty

    Examples:
      | cancel |
      | click the header cancel button |
      | press the ESC key in the new alias input field |

  @modify_entity @save_aliases
  Scenario Outline: Save alias
    When I click the header edit button
      And I enter "alias123" as new aliases
      And I <save>
    Then Aliases list should not be empty
      And Header cancel button should not be there
      And Header save button should not be there
      And Header edit button should be there
      And There should be 1 aliases in the list
      And List of aliases should be "alias123"

    Examples:
      | save |
      | click the header save button |
      | press the RETURN key in the new alias input field |

# T221104
#  @modify_entity @save_aliases
#  Scenario Outline: Save alias and reload
#    When I click the header edit button
#      And I enter "alias123" as new aliases
#      And I <save>
#      And I reload the page
#    Then Header edit button should be there
#      And There should be 1 aliases in the list
#      And List of aliases should be "alias123"
#
#    Examples:
#      | save |
#      | click the header save button |
#      | press the RETURN key in the new alias input field |
#
#  @modify_entity @save_aliases @smoke
#  Scenario: Save multiple aliases
#    When I click the header edit button
#      And I enter "alias1", "alias2", "alias3" as new aliases
#      And I click the header save button
#    Then Aliases list should not be empty
#      And There should be 3 aliases in the list
#      And List of aliases should be "alias1", "alias2", "alias3"
#
#  @modify_entity @save_aliases
#  Scenario: Remove alias
#    When I click the header edit button
#      And I enter "alias1", "alias2" as new aliases
#      And I click the header save button
#      And I click the header edit button
#      And I empty the first alias
#      And I click the header save button
#    Then List of aliases should be "alias2"
#      And There should be 1 aliases in the list
#
#  @modify_entity @save_aliases
#  Scenario: Remove all aliases
#    When I click the header edit button
#      And I enter "alias1", "alias2" as new aliases
#      And I click the header save button
#      And I click the header edit button
#      And I empty the first alias
#      And I empty the first alias
#      And I click the header save button
#    Then Aliases list should be empty
#      And Header edit button should be there
#
#  @modify_entity @save_aliases
#  Scenario: Remove all aliases and reload
#    When I click the header edit button
#      And I enter "alias1", "alias2" as new aliases
#      And I click the header save button
#      And I click the header edit button
#      And I empty the first alias
#      And I empty the first alias
#      And I click the header save button
#      And I reload the page
#    Then Aliases list should be empty
#      And Header edit button should be there
#
#  @ui_only
#  Scenario: Edit aliases UI
#    When I click the header edit button
#      And I enter "alias123" as new aliases
#      And I click the header save button
#      And I click the header edit button
#    Then New alias input field should be there
#      And First alias input field should contain alias123
#      And Header save button should not be there
#      And Header cancel button should be there
#
#  @integration @modify_entity @save_aliases
#  Scenario: Edit multiple aliases
#    When I click the header edit button
#      And I enter "alias1", "alias2" as new aliases
#      And I click the header save button
#      And I click the header edit button
#      And I enter "alias3", "alias4" as new aliases
#      And I empty the first alias
#      And I change the first alias to alias5
#      And I click the header save button
#    Then There should be 3 aliases in the list
#      And List of aliases should be "alias5", "alias3", "alias4"
#
#  @ui_only
#  Scenario: Duplicated aliases detection
#    When I click the header edit button
#      And I enter "alias1", "alias2", "alias1" as new aliases
#    Then Duplicate alias input field should be there
#      And Header save button should be there
#      And Header cancel button should be there
#
#  @ui_only
#  Scenario: Duplicated aliases resolve
#    When I click the header edit button
#      And I enter "alias1", "alias2", "alias1" as new aliases
#      And I empty the first alias
#    Then Duplicate alias input field should not be there
#
#  @modify_entity @save_aliases
#  Scenario: Save duplicated aliases
#    When I click the header edit button
#      And I enter "alias1", "alias2", "alias1" as new aliases
#      And I click the header save button
#    Then There should be 2 aliases in the list
#      And List of aliases should be "alias1", "alias2"
#
#  @modify_entity @save_aliases
#  Scenario Outline: Special inputs for aliases
#    When I click the header edit button
#      And I enter <alias> as new aliases
#      And I click the header save button
#    Then There should be 1 aliases in the list
#      And List of aliases should be <alias_expected>
#
#    Examples:
#      | alias | alias_expected |
#      | "0" | "0" |
#      | "   norm   a lize   me   " | "norm a lize me" |
#      | "<script>$('body').empty();</script>" | "<script>$('body').empty();</script>" |
#
#  @save_aliases
#  Scenario: Too long input for alias
#    When I click the header edit button
#      And I enter "loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong" as new aliases
#      And I click the header save button
#    Then An error message should be displayed
#
#  @bugfix @modify_entity @save_aliases
#  Scenario: Zombie alias bugfix
#    When I click the header edit button
#      And I enter "zombie" as new aliases
#      And I click the header save button
#      And I reload the page
#      And I click the header edit button
#      And I empty the first alias
#      And I click the header save button
#      And I click the header edit button
#      And I enter "alias123" as new aliases
#      And I click the header save button
#    Then There should be 1 aliases in the list
#      And List of aliases should be "alias123"

  @bugfix @ui_only
  Scenario: Bugfix for editbutton appearing when it should not
    When I click the header edit button
      And I click the header cancel button
      And I click the header edit button
    Then Header edit button should not be there
