@wikidata.beta.wmflabs.org
Feature: Delete item

Scenario: Delete item
  Given I am logged in
    And I am on an item page
  When I click the item delete button
  Then Page should be deleted
