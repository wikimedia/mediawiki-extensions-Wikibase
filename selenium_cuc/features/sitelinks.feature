# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item sitelinks tests

Feature: Edit sitelinks

  Background:
    Given I am on an item page

  @ui_only
  Scenario: Sitelink UI has all required elements
    Then Sitelink table should be there
      And Sitelink heading should be there
      And Sitelink add button should be there
      And Sitelink counter should be there
      #And Sitelink counter should show 0
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
  Scenario: Type site id and page name
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

  @save_sitelink @modify_entity
  Scenario Outline: Add sitelink
    Given the sitelinks '<siteid>' / '<pagename>' do not exist
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
      | enwiki | Africa   | English           | Africa              |

  @save_sitelink @modify_entity
  Scenario: Add multiple sitelinks
    Given the sitelinks 'enwiki', 'dewiki', 'itwiki' / 'Europe', 'Europa', 'Europa' do not exist
      When I add 'enwiki', 'dewiki', 'itwiki' / 'Europe', 'Europa', 'Europa' as sitelinks
    Then There should be 3 sitelinks in the list

