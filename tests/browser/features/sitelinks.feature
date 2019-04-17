# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item sitelinks tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @wikidata.beta.wmflabs.org
Feature: Edit sitelinks

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only @test.wikidata.org
  Scenario: Sitelink UI has all required elements
    Then wikipedia sitelink section should be there
      And Sitelink heading should be there
      And Sitelink edit button should be there
      And Sitelink counter should be there
      And There should be 0 sitelinks in the list

  @ui_only @test.wikidata.org
  Scenario: Click edit button
    When I click the sitelink edit button
    Then Sitelink edit button should not be there
      And Sitelink remove button should be disabled
      And Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink help field should be there
      And Sitelink siteid input field should be there
      And Sitelink pagename input field should not be there

  @ui_only @test.wikidata.org
  Scenario Outline: Type site id
    When I click the sitelink edit button
      And I type <siteid> into the 1. siteid input field
    Then Sitelink pagename input field should be there
      And Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink remove button should be disabled
      And Sitelink siteid dropdown should be there
      And Sitelink siteid first suggestion should include <expected_element>

    Examples:
      | siteid | expected_element |
      | en | English |
      | he | עברית  |

# T221104
#  @ui_only @test.wikidata.org
#  Scenario Outline: Type site id and page name
#    When I click the sitelink edit button
#      And I type <siteid> into the 1. siteid input field
#      And I type <pagename> into the 1. page input field
#    Then Sitelink save button should be there
#      And Sitelink cancel button should be there
#      And Sitelink remove button should be there
#      And Sitelink pagename dropdown should be there
#      And Sitelink pagename first suggestion should be <expected_element>
#
#    Examples:
#      | siteid | pagename | expected_element |
#      | en | Main Page | Main Page       |
#      | he | עמוד ראשי | עמוד ראשי |

  @ui_only @test.wikidata.org
  Scenario: Type site id and page name and change site id to something senseless
    When I click the sitelink edit button
      And I type en into the 1. siteid input field
      And I type Main Page into the 1. page input field
      And I type nonexistingwiki into the 1. siteid input field
    Then Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink remove button should be disabled
      And Sitelink pagename input field should not be there

# T221104
#  @ui_only @test.wikidata.org
#  Scenario Outline: Cancel sitelink during siteid selection
#    When I click the sitelink edit button
#      And I <cancel>
#    Then Sitelink edit button should be there
#      And Sitelink cancel button should not be there
#      And Sitelink remove button should not be there
#      And Sitelink save button should not be there
#      And Sitelink siteid input field should not be there
#      And There should be 0 sitelinks in the list
#
#    Examples:
#      | cancel |
#      | click the sitelink cancel button |
#      | press the ESC key in the siteid input field |
#
#  @ui_only @test.wikidata.org
#  Scenario Outline: Cancel sitelink during pagename selection
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I <cancel>
#    Then Sitelink edit button should be there
#      And Sitelink cancel button should not be there
#      And Sitelink remove button should not be there
#      And Sitelink save button should not be there
#      And Sitelink siteid input field should not be there
#      And There should be 0 sitelinks in the list
#
#    Examples:
#      | cancel |
#      | click the sitelink cancel button |
#      | press the ESC key in the pagename input field |
#
#  @modify_entity @save_sitelink @smoke @test.wikidata.org
#  Scenario Outline: Save sitelink
#    Given The following sitelinks do not exist:
#      | enwiki | Asia |
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I type Asia into the 1. page input field
#      And I <save>
#    Then There should be 1 sitelinks in the list
#
#    Examples:
#      | save |
#      | click the sitelink save button |
#      | press the RETURN key in the pagename input field |
#
#  @modify_entity @save_sitelink @test.wikidata.org
#  Scenario Outline: Save sitelink and reload
#    Given The following sitelinks do not exist:
#      | enwiki | Asia |
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I type Asia into the 1. page input field
#      And I <save>
#      And I reload the page
#    Then There should be 1 sitelinks in the list
#
#    Examples:
#      | save |
#      | click the sitelink save button |
#      | press the RETURN key in the pagename input field |
#
#  @modify_entity @save_sitelink @test.wikidata.org
#  Scenario: Edit sitelink
#    Given The following sitelinks do not exist:
#      | enwiki | Asia |
#      | enwiki | Europe |
#    When I add the following sitelinks:
#      | en | Asia |
#      And I reload the page
#      And I click the sitelink edit button
#      And I type Europe into the 1. page input field
#      And I click the sitelink save button
#    Then There should be 1 sitelinks in the list
#      And Sitelink edit button should be there
#      And Sitelink save button should not be there
#      And Sitelink remove button should not be there
#
#  @modify_entity @save_sitelink @test.wikidata.org
#  Scenario Outline: Add sitelink
#    Given The following sitelinks do not exist:
#      | enwiki | Asia   |
#      | sqwiki | Wikipedia |
#    When I click the sitelink edit button
#      And I type <siteid> into the 1. siteid input field
#      And I type <pagename> into the 1. page input field
#      And I click the sitelink save button
#    Then Sitelink edit button should be there
#      And Sitelink save button should not be there
#      And Sitelink cancel button should not be there
#      And Sitelink remove button should not be there
#      And Sitelink siteid input field should not be there
#      And There should be 1 sitelinks in the list
#      And Sitelink language code should include <siteid>
#      And Sitelink link text should be <normalized_pagename>
#      And Sitelink link should lead to article <normalized_pagename>
#
#    Examples:
#      | siteid | pagename | normalized_pagename |
#      | en | Asia   |  Asia                 |
#      | sq | wikipedia  | Wikipedia         |
#
#  @modify_entity @save_sitelink @test.wikidata.org
#  Scenario: Add multiple sitelinks
#    Given The following sitelinks do not exist:
#      | enwiki | Europe |
#      | dewiki | Test |
#      | sqwiki | Wikipedia |
#      When I add the following sitelinks:
#        | en | Europe |
#        | de | Test |
#        | sq | Wikipedia |
#      Then There should be 3 sitelinks in the list
#
#  @modify_entity @save_sitelink
#  Scenario: Remove multiple sitelinks
#    Given The following sitelinks do not exist:
#      | enwiki | Europe |
#      | dewiki | Test |
#      | sqwiki | Wikipedia |
#    When I add the following sitelinks:
#      | en | Europe |
#      | de | Test |
#      | sq | Wikipedia |
#      And I remove all sitelinks
#      And I reload the page
#    Then There should be 0 sitelinks in the list
#      And Sitelink edit button should be there
#
#  @modify_entity @save_sitelink @test.wikidata.org
#  Scenario: List of sitelinks is complete
#    Given The following sitelinks do not exist:
#      | enwiki | Europe |
#    When I add the following sitelinks:
#      | en | Europe |
#      And I mock that the list of sitelinks is complete
#      And I click the sitelink edit button
#    Then Sitelink siteid input field should not be there
#
#  @save_sitelink @test.wikidata.org
#  Scenario: Add sitelink to non existent page
#    When I click the sitelink edit button
#      And I type en into the 1. siteid input field
#      And I type xyz_nonexistentarticle_xyz into the 1. page input field
#      And I click the sitelink save button
#    Then An error message should be displayed for sitelink group wikipedia
#
#  @save_sitelink @test.wikidata.org
#  Scenario: Add new sitelink to already referenced site
#    Given The following sitelinks do not exist:
#      | enwiki | Asia |
#    When I add the following sitelinks:
#      | en | Asia |
#      And I click the sitelink edit button
#      And Sitelink cancel button should be there
#      And I type en into the 2. siteid input field
#    Then Sitelink siteid input field should not be there
#      And Sitelink save button should not be there
