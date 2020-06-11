# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item type statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Creating statements of type item

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

# T221104
#  @integration @modify_entity
#  Scenario Outline: Adding a statement of type item
#    Given I have the following properties with datatype:
#      | itemprop | wikibase-item |
#    Given I have the following items:
#      | item1 |
#    When I click the statement add button
#      And I select the claim property itemprop
#      And I enter the label of item <item> as claim value
#      And I <save>
#    Then Statement add button should be there
#      And Statement cancel button should not be there
#      And Statement save button should not be there
#      And Claim entity selector input element should not be there
#      And Claim value input element should not be there
#      And Statement edit button for claim 1 in group 1 should be there
#      And Statement name of group 1 should be the label of itemprop
#      And Statement value of claim 1 in group 1 should be the label of item <item>
#
#  Examples:
#    | item  | save                            |
#    | item1 | click the statement save button |
#
#  @ui_only
#  Scenario: Select a property, use entity selector
#    Given I have the following properties with datatype:
#      | itemprop | wikibase-item |
#      And I have 3 items beginning with "q"
#    When I click the statement add button
#      And I select the claim property itemprop
#      And I enter q in the claim value input field
#      And I press the ARROWDOWN key in the claim value input field
#      And I press the ARROWDOWN key in the claim value input field
#      And I press the RETURN key in the claim value input field
#      And I memorize the value of the claim value input field
#      And I press the RETURN key in the claim value input field
#    Then Statement value of claim 1 in group 1 should be what I memorized
