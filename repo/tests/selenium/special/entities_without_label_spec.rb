# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for EntitiesWithoutLabel special page

require 'spec_helper'

label = "Malaysia"
description = "Country in Southeast Asia"
sitelinks = [["en", "Malaysia"]]
sitelinks_additional = [["fr", "Malaisie"]]

describe "Check EntitiesWithoutLabel special page" do
  before :all do
    # set up: create item
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
  end
  context "item  test setup" do
    it "should add en/fr sitelinks" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.add_sitelinks(sitelinks)
      end
    end
  end

  context "item by title functionality test" do
    it "should find an item through ItemByTitle special page" do
      visit_page(EntitiesWithoutLabelPage) do |page|
        page.languageField = sitelinks_additional[0][0]
        page.entitiesWithoutLabelSubmit
        page.wait_for_entity_to_load
        page.itemLinkSpan.should_not == label
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
