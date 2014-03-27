# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# feature definition for special set site link tests

@wikidata.beta.wmflabs.org
Feature: Special:SetSiteLink page

  @ui_only
  Scenario: Special:SetSiteLink page has all required elements
    Given I am on SetSiteLink special page
     Then Item id input element should be there
      And Site id input element should be there
      And Page input element should be there

  @ui_only
  Scenario: Special:SetSiteLink page for existing item has required elements
    Given I am on SetSiteLink special page for item
    Then Item id input element should be there
      And Site id input element should be there
      And Page input element should be there