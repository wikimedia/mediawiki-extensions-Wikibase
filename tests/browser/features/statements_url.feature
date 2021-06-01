# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for url type statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Using url properties in statements

  Background:
    Given I have the following properties with datatype:
      | urlprop | url |
      And I am not logged in to the repo

# T221104
#  Scenario Outline: Check UI for invalid values
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property urlprop
#      And I enter <value> in the claim value input field
#      And I click the statement save button
#    Then Statement save button should be there
#      And Statement cancel button should be there
#      And An error message should be displayed
#
#  Examples:
#    | value |
#    | this is no url |
#    | missing.http.org |

  @modify_entity
  Scenario Outline: Adding a statement of type url
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property urlprop
      And I enter http://wikidata.org/ in the claim value input field
      And I <save>
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Claim entity selector input element should not be there
      And Claim value input element should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement link text of claim 1 in group 1 should be http://wikidata.org/

  Examples:
    | save |
    | press the RETURN key in the claim value input field |
    | click the statement save button |

  @modify_entity
  Scenario Outline: Save a statement of type url and check the output
    Given I am logged in to the repo
    And I am on an item page
    And The copyright warning has been dismissed
    When I click the statement add button
    And I select the claim property urlprop
    And I enter <value> in the claim value input field
    And I click the statement save button
    Then Statement save button should not be there
    And Statement cancel button should not be there
    And Statement edit button for claim 1 in group 1 should be there
    And Statement name of group 1 should be the label of urlprop
    And Statement link element of claim 1 in group 1 should be there
    And Statement link text of claim 1 in group 1 should be <value>

  Examples:
    | value |
    | ftp://ftp-stud.hs-esslingen.de/pub |
    | irc://irc.libera.chat/ |
    | mailto:mail@example.com |
    | http://عربي.امارات/en/ |

  @modify_entity
  Scenario: Save a statement of type url and check the link href
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property urlprop
      And I enter http://www.wikimedia.de in the claim value input field
      And I click the statement save button
    Then Statement save button should not be there
      And Statement cancel button should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of urlprop
      And Statement link element of claim 1 in group 1 should be there
      And Statement link text of claim 1 in group 1 should be http://www.wikimedia.de
      And Statement link url of claim 1 in group 1 should be http://www.wikimedia.de/

  @modify_entity
  Scenario: Adding a statement of type url and reload page
    Given I am logged in to the repo
      And I am on an item page
      And The copyright warning has been dismissed
    When I click the statement add button
      And I select the claim property urlprop
      And I enter http://wikidata.org/ in the claim value input field
      And I click the statement save button
      And I reload the page
    Then Statement add button should be there
      And Statement cancel button should not be there
      And Statement save button should not be there
      And Claim entity selector input element should not be there
      And Claim value input element should not be there
      And Statement edit button for claim 1 in group 1 should be there
      And Statement name of group 1 should be the label of urlprop
      And Statement link text of claim 1 in group 1 should be http://wikidata.org/
