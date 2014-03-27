# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# feature definition for special set site link tests

@wikidata.beta.wmflabs.org @special_pages
Feature: Special:SetSiteLink page

  @ui_only
  Scenario: Special:SetSiteLink page has all required elements
    Given I am on the Special:SetSiteLink special page
    Then ID input field should be there
      And Site id input field should be there
      And Page input field should be there
      And Set sitelink button should be there

  @ui_only
  Scenario: Special:SetSiteLink page for existing item has required elements
    Given I have the following items:
        | item1 |
      And I am on the Special:SetSiteLink special page for item item1
    Then ID input field should be there
      And ID input field should contain ID of item item1
      And Site id input field should be there
      And Page input field should be there

  Scenario: Adding a sitelink
    Given I have the following items:
      | item1 |
      And The following sitelinks do not exist:
        | enwiki | Asia |
      And I am on the Special:SetSiteLink special page for item item1
      And I enter the ID of item item1 into the ID input field
      And I enter enwiki into the site id input field
      And I enter Asia into the page input field
      And I press the set sitelink button
    Then There should be 1 sitelinks in the list
