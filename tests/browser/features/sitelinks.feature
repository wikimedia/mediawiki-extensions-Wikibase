# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item sitelinks tests

Feature: Edit sitelinks

  Background:
    Given I am on an item page
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled

  @ui_only
  Scenario: Sitelink UI has all required elements
    Then Sitelink table should be there
      And Sitelink heading should be there
      And Sitelink add button should be there
      And Sitelink counter should be there
      And There should be 0 sitelinks in the list

  @ui_only
  Scenario: Click add button
    When I click the sitelink add button
    Then Sitelink add button should be disabled
      And Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink help field should be there
      And Sitelink siteid input field should be there
      And Sitelink pagename input field should be disabled

  @ui_only
  Scenario Outline: Type site id
    When I click the sitelink add button
      And I type <siteid> into the siteid input field
    Then Sitelink pagename input field should be there
      And Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink add button should be disabled
      And Sitelink siteid dropdown should be there
      And Sitelink siteid first suggestion should be <expected_element>

    Examples:
      | siteid | expected_element |
      | enwiki | English (enwiki) |
      | hewiki | עברית (hewiki) |

  @ui_only
  Scenario Outline: Type site id and page name
    When I click the sitelink add button
      And I type <siteid> into the siteid input field
      And I type <pagename> into the page input field
    Then Sitelink save button should be there
      And Sitelink cancel button should be there
      And Sitelink add button should be disabled
      And Sitelink pagename dropdown should be there
      And Sitelink pagename first suggestion should be <expected_element>

    Examples:
      | siteid | pagename | expected_element |
      | enwiki | Main Page | Main Page       |
      | hewiki | עמוד ראשי | עמוד ראשי |

  @ui_only
  Scenario: Type site id and page name and change site id to something senseless
    When I click the sitelink add button
      And I type enwiki into the siteid input field
      And I type Main Page into the page input field
      And I type nonexistingwiki into the siteid input field
    Then Sitelink save button should be disabled
      And Sitelink cancel button should be there
      And Sitelink add button should be disabled
      And Sitelink pagename input field should be disabled

  @ui_only
  Scenario Outline: Cancel sitelink during siteid selection
    When I click the sitelink add button
      And I <cancel>
    Then Sitelink add button should be there
      And Sitelink cancel button should not be there
      And Sitelink siteid input field should not be there
      And There should be 0 sitelinks in the list

    Examples:
      | cancel |
      | click the sitelink cancel button |
      | press the ESC key in the siteid input field |

  @ui_only
  Scenario Outline: Cancel sitelink during pagename selection
    When I click the sitelink add button
      And I type enwiki into the siteid input field
      And I <cancel>
    Then Sitelink add button should be there
      And Sitelink cancel button should not be there
      And Sitelink siteid input field should not be there
      And There should be 0 sitelinks in the list

    Examples:
      | cancel |
      | click the sitelink cancel button |
      | press the ESC key in the pagename input field |

  @save_sitelink @modify_entity @smoke
  Scenario Outline: Save sitelink
    Given The following sitelinks do not exist:
      | enwiki | Asia |
    When I click the sitelink add button
      And I type enwiki into the siteid input field
      And I type Asia into the page input field
      And I <save>
    Then There should be 1 sitelinks in the list

    Examples:
      | save |
      | click the sitelink save button |
      | press the RETURN key in the pagename input field |

  @save_sitelink @modify_entity
  Scenario Outline: Save sitelink & reload
    Given The following sitelinks do not exist:
      | enwiki | Asia |
    When I click the sitelink add button
      And I type enwiki into the siteid input field
      And I type Asia into the page input field
      And I <save>
      And I reload the page
    Then There should be 1 sitelinks in the list

    Examples:
      | save |
      | click the sitelink save button |
      | press the RETURN key in the pagename input field |

  @save_sitelink @modify_entity
  Scenario: Edit sitelink
    Given The following sitelinks do not exist:
      | enwiki | Asia |
      | enwiki | Europe |
    When I add the following sitelinks:
      | enwiki | Asia |
      And I reload the page
      And I click the sitelink edit button
      And I type Europe into the page input field
      And I click the sitelink save button
    Then There should be 1 sitelinks in the list
      And Sitelink add button should be there
      And Sitelink edit button should be there
      And Sitelink save button should not be there

  @save_sitelink @modify_entity
  Scenario Outline: Add sitelink
    Given The following sitelinks do not exist:
      | <siteid> | <normalized_pagename> |
    When I click the sitelink add button
      And I type <siteid> into the siteid input field
      And I type <pagename> into the page input field
      And I click the sitelink save button
    Then Sitelink add button should be there
      And Sitelink edit button should be there
      And Sitelink save button should not be there
      And Sitelink cancel button should not be there
      And Sitelink siteid input field should not be there
      And There should be 1 sitelinks in the list
      And Sitelink language table cell should contain <expected_language>
      And Sitelink code table cell should contain <siteid>
      And Sitelink link text should be <normalized_pagename>
      And Sitelink link should lead to article <normalized_pagename>

    Examples:
      | siteid | pagename | expected_language | normalized_pagename |
      | enwiki | Asia   | English   | Asia                |
      | sqwiki | wikipedia  | shqip | Wikipedia           |

  @save_sitelink @modify_entity
  Scenario: Add multiple sitelinks
    Given The following sitelinks do not exist:
      | enwiki | Europe |
      | dewiki | Testseite |
      | sqwiki | Wikipedia |
      When I add the following sitelinks:
        | enwiki | Europe |
        | dewiki | Testseite |
        | sqwiki | Wikipedia |
      Then There should be 3 sitelinks in the list

  @save_sitelink @modify_entity
  Scenario: Remove multiple sitelinks
    Given The following sitelinks do not exist:
      | enwiki | Europe |
      | dewiki | Testseite |
      | sqwiki | Wikipedia |
    When I add the following sitelinks:
      | enwiki | Europe |
      | dewiki | Testseite |
      | sqwiki | Wikipedia |
      And I remove all sitelinks
      And I reload the page
    Then There should be 0 sitelinks in the list
      And Sitelink add button should be there
      And Sitelink edit button should not be there

  @save_sitelink @modify_entity
  Scenario: Initial sorting of sitelinks
    Given The following sitelinks do not exist:
      | enwiki | Rashidun |
      | dewiki | Error |
      | sqwiki | Wikipedia |
      | simplewiki | Wiktionary |
    When I add the following sitelinks:
      | enwiki | Rashidun |
      | dewiki | Error |
      | sqwiki | Wikipedia |
      | simplewiki | Wiktionary |
    And I reload the page
    Then There should be 4 sitelinks in the list
     And Order of sitelinks should be:
       | dewiki | enwiki | simplewiki | sqwiki |

  @save_sitelink @modify_entity
  Scenario: Sorting sitelinks by languagename
    Given The following sitelinks do not exist:
      | enwiki | Rashidun |
      | dewiki | Error |
      | sqwiki | Wikipedia |
      | simplewiki | Wiktionary |
    When I add the following sitelinks:
      | enwiki | Rashidun |
      | dewiki | Error |
      | sqwiki | Wikipedia |
      | simplewiki | Wiktionary |
    And I reload the page
    And I order the sitelinks by languagename
    Then There should be 4 sitelinks in the list
      And Order of sitelinks should be:
        | dewiki | enwiki | sqwiki | simplewiki |

  @save_sitelink @modify_entity
  Scenario: List of sitelinks is complete
    Given The following sitelinks do not exist:
      | enwiki | Europe |
      | dewiki | Testseite |
    When I add the following sitelinks:
      | enwiki | Europe |
      And I mock that the list of sitelinks is complete
      And I add the following sitelinks:
        | dewiki | Testseite |
    Then Sitelink add button should be disabled

  @save_sitelink
  Scenario: Add sitelink to non existent page
    When I click the sitelink add button
      And I type enwiki into the siteid input field
      And I type xyz_nonexistentarticle_xyz into the page input field
      And I click the sitelink save button
    Then An error message should be displayed
