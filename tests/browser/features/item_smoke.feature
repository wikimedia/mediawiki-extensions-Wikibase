# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item smoke test

@smoke @firefox @chrome @internet_explorer_10 @internet_explorer_11
Feature: Item smoke test

  Scenario Outline: Check UI elements
    When I navigate to item <item_id> with resource loader debug mode <debug_mode>
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled
    Then Header edit button should be there
      And Label element should be there
      And <label> should be displayed as label
      And <item_id> should be displayed as entity id next to the label
      And Header edit button should be there
      And Description element should be there
      And <description> should be displayed as description
      And Header edit button should be there
      And List of aliases should be <aliases>
      And There should be <num_aliases> aliases in the list
      And Header edit button should be there
      And Statements heading should be there
      And Statement add button should be there
      And Statement add button for group 1 should be there
      And Statement add button for group 2 should be there
      And Statement add button for group 17 should be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement edit button for claim 2 in group 1 should be there
      And Statement edit button for claim 1 in group 17 should be there
      And Statement edit button for claim 5 in group 17 should be there
      And Sitelink heading should be there
      And Sitelink edit button should be there
      And Sitelink counter should be there
      And There should be <num_sitelinks> sitelinks in the list

  @wikidata.beta.wmflabs.org
  Examples:
    | item_id | label | description | aliases | num_aliases | num_sitelinks | debug_mode |
    | Q15905  | "Italy" | "republic in Southern Europe" | "Italia", "Italian Republic", "Italië" | 3 | 2 | false |
    | Q15905  | "Italy" | "republic in Southern Europe" | "Italia", "Italian Republic", "Italië" | 3 | 2 | true |

# T221104
#  Scenario Outline: Click UI elements
#    When I navigate to item <item_id> with resource loader debug mode <debug_mode>
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#      And I click the header edit button
#      And I click the header cancel button
#      And I click the statement add button
#      And I click the statement cancel button
#      And I click the sitelink edit button
#      And I click the sitelink cancel button
#    Then Header edit button should be there
#      And Statement add button for group 1 should be there
#      And Statement add button should be there
#      And Sitelink edit button should be there
#
#  @wikidata.beta.wmflabs.org
#  Examples:
#    | item_id | debug_mode |
#    | Q15905  | false |
#    | Q15905  | true |
