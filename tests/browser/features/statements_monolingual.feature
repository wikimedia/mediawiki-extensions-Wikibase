# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for monolingual type statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Using monolingual properties in statements

  Background:
    Given I have the following properties with datatype:
      | monolingprop | monolingualtext |
      And I am not logged in to the repo

# T221104
#  @ui_only
#  Scenario: Monolingual UI should work properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property monolingprop
#      And I enter something in the claim value input field
#    Then Statement save button should not be there
#      And Statement cancel button should be there
#      And InputExtender input should be there
#
#  @ui_only
#  Scenario: Check monolingual for invalid language values
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property monolingprop
#      And I enter something in the claim value input field
#      And I enter definitelynotalanguage in the InputExtender input field
#      And I click the statement save button
#    Then Statement save button should be there
#      And Statement cancel button should be there

  @modify_entity
  Scenario Outline: Adding a statement of type monolingual
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property monolingprop
      And I enter something in the claim value input field
      And I enter <language> in the InputExtender input field
      And I click the InputExtender dropdown first element
      And I click the statement save button
    Then Statement string value of claim 1 in group 1 should be something (<language>)
      And Statement name of group 1 should be the label of monolingprop
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there

  Examples:
    | language |
    | English |
    | German |

# T221104
#  Scenario Outline: Adding a statement of type monolingual and use keyboard to select language
#    Given I am on an item page
#      And The copyright warning has been dismissed
#    When I click the statement add button
#      And I select the claim property monolingprop
#      And I enter something in the claim value input field
#      And I enter <language> in the InputExtender input field
#      And I press the ARROWDOWN key in the InputExtender input field
#      And I press the RETURN key in the InputExtender input field
#      And I click the statement save button
#    Then Statement string value of claim 1 in group 1 should be something (<language>)
#      And Statement name of group 1 should be the label of monolingprop
#      And Statement save button should not be there
#      And Statement cancel button should not be there
#      And Statement edit button for claim 1 in group 1 should be there
#
#  Examples:
#    | language |
#    | English |
#    | German |

  @modify_entity
  Scenario: Adding a statement of type monolingual and reload page
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property monolingprop
      And I enter something in the claim value input field
      And I enter English in the InputExtender input field
      And I click the InputExtender dropdown first element
      And I click the statement save button
      And I reload the page
    Then Statement string value of claim 1 in group 1 should be something (English)
      And Statement name of group 1 should be the label of monolingprop
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there
