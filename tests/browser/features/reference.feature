# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for references

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Adding references to statements

  Background:
    Given I have an item to test
      And I have the following properties with datatype:
        | stringprop | string |
      And I have statements with the following properties and values:
        | stringprop | reference test |
      And I am on the page of the item to test
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Reference UI has all required elements
    When I click the statement edit button
    Then Reference add button should be there
      And Statement save button should not be there
      And Reference counter should be there
      And Reference counter should show 0

# T221104
#  @ui_only
#  Scenario: References toggler
#    When I click the toggle references link of statement 1
#    Then Reference add button should not be there
#      And Statement save button should not be there
#      And Reference counter should be there
#      And Reference counter should show 0
#
#  @ui_only
#  Scenario: Click the Add Reference button
#    When I click the statement edit button
#      And I click the reference add button
#    Then Reference add button should be there
#      And Statement save button should be disabled
#      And Reference add snak button should be there
#      And Reference remove snak button should be there
#      And Reference remove button should be there
#      And Statement cancel button should be there
#      And Snak entity selector input element should be there
#      And Snak value input element should not be there
#
#  @ui_only
#  Scenario Outline: Cancel reference
#    When I click the statement edit button
#      And I click the reference add button
#      And I close the entity selector popup if present
#      And I <cancel>
#      And I click the statement edit button
#    Then Reference add button should be there
#      And Reference counter should be there
#      And Reference counter should show 0
#      And Statement cancel button should be there
#      And Statement save button should not be there
#      And Reference remove button should not be there
#      And Reference add snak button should not be there
#      And Reference remove snak button should not be there
#
#  Examples:
#    | cancel |
#    | click the statement cancel button |
#    | press the ESC key in the snak entity selector input field |

  @ui_only
  Scenario: Select a property
    When I click the statement edit button
      And I click the reference add button
      And I select the snak property stringprop
    Then Reference add button should be there
      And Statement save button should be disabled
      And Reference add snak button should be there
      And Reference remove snak button should be there
      And Reference remove button should be there
      And Statement cancel button should be there
      And Snak entity selector input element should be there
      And Snak value input element should be there

  @ui_only
  Scenario: Select a property and enter a value
    When I click the statement edit button
      And I click the reference add button
      And I select the snak property stringprop
      And I enter something as string snak value
    Then Reference add button should be there
      And Reference remove snak button should be there
      And Reference remove button should be there
      And Statement save button should be there
      And Reference add snak button should be there
      And Statement cancel button should be there
      And Snak entity selector input element should be there
      And Snak value input element should be there

  @modify_entity
  Scenario Outline: Add reference with one snak
    When I click the statement edit button
      And I click the reference add button
      And I select the snak property stringprop
      And I enter test as string snak value
      And I <save>
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Reference add snak button should not be there
      And Reference remove snak button should not be there
      And Snak entity selector input element should not be there
      And Snak value input element should not be there
      And Property of snak 1 of reference 1 should be linked
      And Property of snak 1 of reference 1 should be label of stringprop
      And Value of snak 1 of reference 1 should be test
      And Reference counter should show 1
  Examples:
    | save |
    | click the statement save button |
    | press the RETURN key in the snak value input field |

  @integration @modify_entity
  Scenario: Add reference with multiple snaks
    Given I have the following properties with datatype:
        | stringprop1 | string |
        | stringprop2 | string |
        | stringprop3 | string |
      And I add the following reference snaks:
        | stringprop1 | test1 |
        | stringprop2 | test2 |
        | stringprop3 | test3 |
      And I reload the page
      And I click the toggle references link of statement 1
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Reference add snak button should not be there
      And Reference remove snak button should not be there
      And Snak entity selector input element should not be there
      And Snak value input element should not be there
      And Property of snak 1 of reference 1 should be linked
      And Property of snak 2 of reference 1 should be linked
      And Property of snak 3 of reference 1 should be linked
      And Property of snak 1 of reference 1 should be label of stringprop1
      And Property of snak 2 of reference 1 should be label of stringprop2
      And Property of snak 3 of reference 1 should be label of stringprop3
      And Value of snak 1 of reference 1 should be test1
      And Value of snak 2 of reference 1 should be test2
      And Value of snak 3 of reference 1 should be test3
      And Reference counter should show 1

  @modify_entity
  Scenario: Check UI elements when editing reference
    Given I have the following properties with datatype:
        | stringprop | string |
      And I add the following reference snaks:
        | stringprop | test |
      And I click the statement edit button
    Then Statement save button should be disabled
      And Statement cancel button should be there
      And Reference remove button should be there
      And Reference remove snak button should be there
      And Reference add snak button should be there
      And Reference add button should be there
      And Snak entity selector input element should not be there
      And Snak value input element should be there

  @modify_entity
  Scenario: Edit reference with one snak
    Given I have the following properties with datatype:
        | stringprop | string |
      And I add the following reference snaks:
        | stringprop | test |
      And I click the statement edit button
      And I enter modified as string snak value
    And I click the statement save button
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Reference add snak button should not be there
      And Reference remove snak button should not be there
      And Snak entity selector input element should not be there
      And Snak value input element should not be there
      And Property of snak 1 of reference 1 should be linked
      And Property of snak 1 of reference 1 should be label of stringprop
      And Value of snak 1 of reference 1 should be modified
      And Reference counter should show 1

  @modify_entity
  Scenario: Remove complete reference
    Given I have the following properties with datatype:
        | stringprop | string |
      And I add the following reference snaks:
        | stringprop | test |
      And I click the statement edit button
      And I click the reference remove button
    Then Reference add button should be there
      And Statement cancel button should be there
      And Statement save button should be there
      And Reference counter should be there
      And Reference counter should show 0
      And Snak entity selector input element should not be there
      And Snak value input element should not be there
      And Property of snak 1 of reference 1 should not be there
      And Value of snak 1 of reference 1 should not be there

  @modify_entity
  Scenario: Remove reference snak
    Given I have the following properties with datatype:
        | stringprop1 | string |
        | stringprop2 | string |
      And I add the following reference snaks:
        | stringprop1 | test1 |
        | stringprop2 | test2 |
      And I click the statement edit button
      And I remove reference snak 1
      And I click the statement save button
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Reference counter should be there
      And Reference counter should show 1
      And Snak entity selector input element should not be there
      And Snak value input element should not be there
      And Property of snak 1 of reference 1 should be label of stringprop2
      And Value of snak 1 of reference 1 should be test2
      And Property of snak 2 of reference 1 should not be there
      And Value of snak 2 of reference 1 should not be there
