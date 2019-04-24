# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item sitelinks tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Add badges to sitelinks

  Background:
    Given I have at least 2 badges to test
      And I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

# T221104
#  @ui_only
#  Scenario: Sitelink badge UI is there
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#    Then Sitelink pagename input field should be there
#      And Sitelink badge selector should be there
#      And Sitelink empty badge selector should be there
#
#  @ui_only
#  Scenario: Sitelink badge UI shows all available badges
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I click the empty badge selector
#    Then Sitelink pagename input field should be there
#      And Sitelink badge selector menu should be there
#      And Sitelink badge selector menu should show available badges

  @ui_only
  Scenario: Choose a badge
    When I click the sitelink edit button
      And I type en into the 1. siteid input field
      And I click the empty badge selector
      And I click the 1. badge selector id item
    Then Sitelink empty badge selector should not be there
      And The 1. badge id should be attached to the sitelink

# T221104
#  @ui_only
#  Scenario: Choose multiple badges
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I click the empty badge selector
#      And I click the 1. badge selector id item
#      And I click the 2. badge selector id item
#    Then Sitelink empty badge selector should not be there
#      And The 1. badge id should be attached to the sitelink
#      And The 2. badge id should be attached to the sitelink
#
#  @modify_entity @save_sitelink
#  Scenario: Save a badge
#    Given The following sitelinks do not exist:
#      | enwiki | Asia |
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I type Asia into the 1. page input field
#      And I click the empty badge selector
#      And I click the 1. badge selector id item
#      And I click the sitelink save button
#    Then Sitelink empty badge selector should not be there
#      And The 1. badge id should be attached to the sitelink
#      And Sitelink save button should not be there
#      And Sitelink cancel button should not be there
#      And Sitelink edit button should be there
#
#  @modify_entity @save_sitelink
#  Scenario: Save a badge and reload
#    Given The following sitelinks do not exist:
#      | enwiki | Asia |
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I type Asia into the 1. page input field
#      And I click the empty badge selector
#      And I click the 1. badge selector id item
#      And I click the sitelink save button
#      And I reload the page
#    Then Sitelink empty badge selector should not be there
#      And The 1. badge id should be attached to the sitelink
#      And Sitelink save button should not be there
#      And Sitelink cancel button should not be there
#      And Sitelink edit button should be there
