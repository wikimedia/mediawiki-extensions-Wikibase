# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for ItemByTitle special page

require 'spec_helper'

label = "Malaysia"
description = "Country in Southeast Asia"
sitelinks = [["en", "Malaysia"], ["fr", "Malaisie"]]

describe "Check ItemByTitle special page" do
  before :all do
    # set up: create item
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
  end
  context "item by title test setup" do
    it "should add en/fr sitelinks" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.add_sitelinks(sitelinks)
      end
    end
  end

  context "item by title functionality test" do
    it "should find an item through ItemByTitle special page" do
      visit_page(ItemByTitlePage) do |page|
        page.itemByTitleSiteField = sitelinks[0][0] + "wiki"
        page.itemByTitlePageField = sitelinks[0][1]
        page.itemByTitleSubmit
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
        @browser.back
        page.itemByTitleSiteField = sitelinks[1][0] + "wiki"
        page.itemByTitlePageField = sitelinks[1][1]
        page.itemByTitleSubmit
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
  end
end
