# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item label tests

Feature: Edit label

  Background:
    Given I am on an item page

  @ui_only
  Scenario: Label UI has all required elements
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there

  @ui_only
  Scenario: Click edit button
    When I click the label edit button
    Then Label input element should be there
      And Label input element should contain original label
      And Label cancel button should be there

  @ui_only
  Scenario: Modify the label
    When I click the label edit button
      And I enter MODIFIED LABEL as label
    Then Label save button should be there
      And Label cancel button should be there
      And Label edit button should not be there

  @ui_only
  Scenario Outline: Cancel label
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I <cancel>
    Then Original label should be displayed
      And Label edit button should be there
      And Label cancel button should not be there

    Examples:
      | cancel |
      | click the label cancel button |
      | press the ESC key in the label input field |

  @save_label @modify_entity
  Scenario Outline: Save label
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I <save>
    Then MODIFIED LABEL should be displayed as label

    Examples:
      | save |
      | click the label save button |
      | press the RETURN key in the label input field |

  @save_label @modify_entity
  Scenario Outline: Save label and reload
    When I click the label edit button
      And I enter MODIFIED LABEL as label
      And I <save>
      And I reload the page
    Then MODIFIED LABEL should be displayed as label

    Examples:
      | save |
      | click the label save button |
      | press the RETURN key in the label input field |

  @save_label @modify_entity
  Scenario Outline: Label with special input
    When I click the label edit button
      And I enter <label> as label
      And I click the label save button
    Then <expected_label> should be displayed as label

    Examples:
      | label | expected_label |
      | 0           | 0                    |
      |    norm    a   lize  me   | norm a lize me |
      | <script>$("body").empty();</script> | <script>$("body").empty();</script> |
      | {{Template:blabla}} | {{Template:blabla}} |

  @save_label
  Scenario: Label with a too long value
    When I click the label edit button
      And I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong as label
      And I click the label save button
    Then An error message should be displayed
