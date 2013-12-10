@wikidata.beta.wmflabs.org
Feature: Delete item

Scenario: Delete item
  Given I am logged in
    And I am on the item page
    And item parameters are not empty
  When I click the item delete button
    And click Delete page
  Then Page should be deleted
