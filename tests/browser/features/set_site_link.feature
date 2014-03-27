# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# feature definition for special set site link tests

@wikidata.beta.wmflabs.org
Feature: Special set site link

  @ui_only
  Scenario: Set site link UI has all required elements
    Given I am on SetSiteLink special page
     Then Entity id input element should be there
      And Site id input element should be there
      And Page input element should be there