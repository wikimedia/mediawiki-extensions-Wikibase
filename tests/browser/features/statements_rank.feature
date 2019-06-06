# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for statement ranks tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Setting ranks of statements

  Background:
    Given I have an item to test
      And I have the following properties with datatype:
        | stringprop | string |
      And I have statements with the following properties and values:
        | stringprop | rank test |
      And I am on the page of the item to test
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Check indicated rank
    Then Rank selector for claim 1 in group 1 should be disabled
      And Indicated rank for claim 1 in group 1 should be normal

  @ui_only
  Scenario: Click the rank selector
    When I edit claim 1 in group 1
      And I click the rank selector of claim 1 in group 1
    Then Rank selector for claim 1 in group 1 should be there
      And Rank selector menu should be visible
      And Rank selector item for normal rank should be visible
      And Rank selector item for preferred rank should be visible
      And Rank selector item for deprecated rank should be visible
      And Statement save button should be disabled

  @ui_only
  Scenario: Change the rank
    When I edit claim 1 in group 1
      And I click the rank selector of claim 1 in group 1
      And I select preferred rank for claim 1 in group 1
    Then Rank selector for claim 1 in group 1 should be there
      And Rank selector menu should not be visible
      And Rank selector item for normal rank should not be visible
      And Rank selector item for preferred rank should not be visible
      And Rank selector item for deprecated rank should not be visible
      And Statement save button should be there

  @modify_entity
  Scenario: Change the rank and save
    When I edit claim 1 in group 1
      And I click the rank selector of claim 1 in group 1
      And I select preferred rank for claim 1 in group 1
      And I click the statement save button
    Then Rank selector for claim 1 in group 1 should be disabled
      And Rank selector menu should not be visible
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Indicated rank for claim 1 in group 1 should be preferred
      And Statement edit button for claim 1 in group 1 should be there
      And Claim value input element should not be there

  @integration @modify_entity
  Scenario: Change the rank, save and reload
    When I edit claim 1 in group 1
      And I click the rank selector of claim 1 in group 1
      And I select preferred rank for claim 1 in group 1
      And I click the statement save button
      And I reload the page
    Then Rank selector for claim 1 in group 1 should be disabled
      And Rank selector menu should not be visible
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Indicated rank for claim 1 in group 1 should be preferred
      And Statement edit button for claim 1 in group 1 should be there
      And Claim value input element should not be there
