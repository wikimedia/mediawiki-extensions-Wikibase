# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for statement snaktype tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Setting snaktypes of statements

  Background:
    Given I have an item to test
      And I have the following properties with datatype:
        | stringprop | string |
      And I have statements with the following properties and values:
        | stringprop | snaktype test |
      And I am on the page of the item to test
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Click the snaktype selector
    When I edit claim 1 in group 1
      And I click the snaktype selector of claim 1 in group 1
    Then Snaktype selector for claim 1 in group 1 should be there
      And Snaktype selector menu should be visible
      And Snaktype selector item for novalue snaktype should be visible
      And Snaktype selector item for somevalue snaktype should be visible
      And Snaktype selector item for value snaktype should be visible
      And Statement save button should be disabled

  @ui_only
  Scenario Outline: Change the snaktype
    When I edit claim 1 in group 1
      And I click the snaktype selector of claim 1 in group 1
      And I select <snaktype> snaktype for claim 1 in group 1
    Then Snaktype selector for claim 1 in group 1 should be there
      And Snaktype selector menu should not be visible
      And Snaktype selector item for novalue snaktype should not be visible
      And Snaktype selector item for somevalue snaktype should not be visible
      And Snaktype selector item for value snaktype should not be visible
      And Statement save button should be there
      And Snaktype <snaktype> should be shown for claim 1 in group 1

    Examples:
    | snaktype  |
    | novalue   |
    | somevalue |

  @modify_entity
  Scenario Outline: Change the snaktype and save
    When I edit claim 1 in group 1
      And I click the snaktype selector of claim 1 in group 1
      And I select <snaktype> snaktype for claim 1 in group 1
      And I click the statement save button
    Then Snaktype selector for claim 1 in group 1 should not be there
      And Snaktype selector menu should not be visible
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Snaktype <snaktype> should be shown for claim 1 in group 1
      And Statement edit button for claim 1 in group 1 should be there
      And Claim value input element should not be there

  Examples:
    | snaktype  |
    | novalue   |
    | somevalue |

  @modify_entity
  Scenario Outline: Change the snaktype, save and reload
    When I edit claim 1 in group 1
      And I click the snaktype selector of claim 1 in group 1
      And I select <snaktype> snaktype for claim 1 in group 1
      And I click the statement save button
      And I reload the page
    Then Snaktype selector for claim 1 in group 1 should not be there
      And Snaktype selector menu should not be visible
      And Statement save button should not be there
      And Statement cancel button should not be there
      And Snaktype <snaktype> should be shown for claim 1 in group 1
      And Statement edit button for claim 1 in group 1 should be there
      And Claim value input element should not be there

  Examples:
    | snaktype  |
    | novalue   |
    | somevalue |
