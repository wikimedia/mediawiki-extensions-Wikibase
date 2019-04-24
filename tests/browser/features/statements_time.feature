# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for time type statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Using time properties in statements

  Background:
    Given I have the following properties with datatype:
      | timeprop | time |
      And I am not logged in to the repo

# T221104
#  Scenario: Time UI should work properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property timeprop
#      And I enter 1 in the claim value input field
#    Then Statement save button should be there
#      And Statement cancel button should be there
#      And InputExtender preview should be there
#      And Time precision chooser should be there
#      And Time calendar chooser should be there
#
#  Scenario Outline: Check UI for invalid values
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property timeprop
#      And I enter <value> in the claim value input field
#    Then Statement save button should not be there
#      And Statement cancel button should be there
#
#  Examples:
#    | value |
#    | astring |
#    | 1 11 199 9 |
#    | 1 AC |
#    | 32.12.2015 |
##    | 1.9.1999 12:12 | TODO: currently disabled see as well T102930
#
#  Scenario Outline: Time parser in the preview and precision detection should work properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property timeprop
#      And I enter <value> in the claim value input field
#    Then Statement save button should be there
#      And Statement cancel button should be there
#      And <preview> should be displayed in the InputExtender preview
#      And <calendar> should be the time calendar setting
#      And <precision> should be the time precision setting
#
#  Examples:
#    | value | preview | calendar | precision |
#    | 1 | 1 | Julian | year |
#    | 1 1 | January 1 | Julian | month |
#    | 1 1 1999 | 1 January 1999 | Gregorian | day |
#    | 12.11.1981 | 12 November 1981 | Gregorian | day |
#    | 1 bc | 1 BCE | Julian | year |
#    | 1 b.c. | 1 BCE | Julian | year |
#    | 1 ad | 1 | Julian | year |
#    | 1 ce | 1 | Julian | year |
#    | 10000 | 10000 years CE | Gregorian | 10,000 years |
#    | 100000 | 100000 years CE | Gregorian | 100,000 years |
#    | 1000000 BC | 1 million years BCE | Julian | million years |
#    | 10000000 | 10 million years CE | Gregorian | ten million years |
#    | 100000000 | 100 million years CE | Gregorian | hundred million years |
#    | 1000000000 BCE | 1 billion years BCE | Julian | billion years |

  @integration @modify_entity
  Scenario Outline: Adding a statement of type time
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property timeprop
      And I enter 14.05.1985 in the claim value input field
      And I <save>
    Then Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of timeprop
      And Statement string value of claim 1 in group 1 should be 14 May 1985

  Examples:
    | save |
    | click the statement save button |
    | press the RETURN key in the claim value input field |

  @modify_entity
  Scenario: Adding a statement of type time and reload page
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property timeprop
      And I enter 14.05.1985 in the claim value input field
      And I click the statement save button
      And I reload the page
    Then Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of timeprop
      And Statement string value of claim 1 in group 1 should be 14 May 1985
