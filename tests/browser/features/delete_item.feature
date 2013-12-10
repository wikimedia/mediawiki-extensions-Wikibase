# Wikidata delete test
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature  delete of items

@wikidata.beta.wmflabs.org
Feature: Delete Item

  Background:
    Given I am on an item page
      And I have the permission to delete
	

  @ui_only
  Scenario: Item UI has all required elements
    Then every item lement should be displayed
      And delete button should be there
 

  @ui_only
  Scenario: Click delete button
    When I click the delete button
    A warning message should appear
      And it should show: "You are about to delete a page along with all of its history. Please confirm that you intend to do this, that you understand the consequences, and that you are doing this in accordance with the policy. "

  @ui_only
  Scenario: Delete the page
    When I click the delete button
      And click delete page
    Then the page should be now be deleted


    Examples:
      | delete |
      | delete page|
      | Page has been deleted|


