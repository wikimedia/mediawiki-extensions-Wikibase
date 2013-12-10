@wikidata.beta.wmflabs.org
Feature: Non existing item

Scenario: Edit tab
  Given I am on an non existing item page
  Then check if this page behaves correctly
