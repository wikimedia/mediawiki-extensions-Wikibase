# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for property smoke test

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @smoke
Feature: Property smoke test

  @smoke
  Scenario Outline: Check UI elements
    When I navigate to property <property_id> with resource loader debug mode <debug_mode>
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled
    Then Header edit button should be there
      And Label element should be there
      And <label> should be displayed as label
      And <property_id> should be displayed as entity id next to the label
      And Header edit button should be there
      And Description element should be there
      And <description> should be displayed as description
      And Header edit button should be there
      And List of aliases should be <aliases>
      And There should be <num_aliases> aliases in the list
      And Header edit button should be there
      And Property datatype heading should be there
      And Property datatype should display <datatype>
      And Statements heading should be there
      And Statement add button should be there
      And Sitelink heading should not be there
      And Sitelink edit button should not be there

  # TODO: this is quite fragile tests, which is going to fail every time data of P694 on
  # beta wikidata is changed, how to make it more stable?
  @wikidata.beta.wmflabs.org
  Examples:
    | property_id | label | description | aliases | num_aliases | datatype | debug_mode |
    | P694 | "instance of" | "this item is a concrete object (instance) of this class, category or object group" | "is a", "is an", "rdf:type" | 3 | Item | false |
    | P694 | "instance of" | "this item is a concrete object (instance) of this class, category or object group" | "is a", "is an", "rdf:type" | 3 | Item  | true |

# T221104
#  @smoke
#  Scenario Outline: Click UI elements
#    When I navigate to property <property_id> with resource loader debug mode <debug_mode>
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#      And I click the header edit button
#      And I click the header cancel button
#    Then Header edit button should be there
#      And Statement add button should be there
#      And Sitelink edit button should not be there
#
#  @wikidata.beta.wmflabs.org
#  Examples:
#    | property_id | debug_mode |
#    | P694 | false |
#    | P694 | true  |
#
#  @smoke @wikidata.beta.wmflabs.org
#  Scenario: Click statement add button
#    When I navigate to property id P694
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#      And I click the statement add button
#    Then Statement add button should be there
#      And Statement cancel button should be there
#      And Statement save button should be disabled
#      And Statement help field should be there
#      And Claim entity selector input element should be there
#      And Claim value input element should not be there
#      And Rank selector for claim 1 in group 1 should be there
#      And Snaktype selector for claim 1 in group 1 should not be there

  @smoke @modify_entity
  Scenario: Save statement
    Given I have the following properties with datatype:
      | stringprop | string |
    When I navigate to property handle stringprop
      And The copyright warning has been dismissed
      And Anonymous edit warnings are disabled
      And I click the statement add button
      And I select the claim property stringprop
      And I enter it's a string in the claim value input field
      And I click the statement save button
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Claim entity selector input element should not be there
      And Claim value input element should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of stringprop
      And Statement string value of claim 1 in group 1 should be it's a string
