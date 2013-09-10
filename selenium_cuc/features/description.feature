# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item description tests

Feature: Edit description

  Background:
    Given I am on an item page

  @ui_only
  Scenario: Description UI has all required elements
    Then Original description should be displayed
      And Description edit button should be there
      And Description cancel button should not be there

  @ui_only
  Scenario: Click edit button
    When I click the description edit button
    Then Description input element should be there
      And Description input element should contain original description
      And Description cancel button should be there

  @ui_only
  Scenario: Modify the description
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
    Then Description save button should be there
      And Description cancel button should be there
      And Description edit button should not be there

  @ui_only
  Scenario Outline: Cancel description
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I <cancel>
    Then Original description should be displayed
      And Description edit button should be there
      And Description cancel button should not be there

    Examples:
      | cancel |
      | click the description cancel button |
      | press the ESC key in the description input field |

  @save_description @modify_entity
  Scenario Outline: Save description
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I <save>
    Then MODIFIED DESCRIPTION should be displayed as description

    Examples:
     | save |
     | click the description save button |
     | press the RETURN key in the description input field |

  @save_description @modify_entity
  Scenario Outline: Save description
    When I click the description edit button
      And I enter MODIFIED DESCRIPTION as description
      And I <save>
      And I reload the page
    Then MODIFIED DESCRIPTION should be displayed as description

    Examples:
      | save |
      | click the description save button |
      | press the RETURN key in the description input field |

  @save_description @modify_entity
  Scenario Outline: Description with special input
    When I click the description edit button
      And I enter <description> as description
      And I click the description save button
    Then <expected_description> should be displayed as description

    Examples:
      | description | expected_description |
      | 0           | 0                    |
      |    norm    a   lize  me   | norm a lize me |
      | <script>$("body").empty();</script> | <script>$("body").empty();</script> |

  @save_description
  Scenario: Description with a too long value
    When I click the description edit button
      And I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong as description
      And I click the description save button
    Then An error message should be displayed
