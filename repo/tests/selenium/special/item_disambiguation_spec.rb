# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for ItemDisambiguation special page

require 'spec_helper'

label_ab = "Apache"
description_a = "Helicopter"
description_b = "Webserver"

describe "Check item disambiguation special page" do
  before :all do
    # set up: create 2 items with same label but different description
    visit_page(CreateItemPage) do |page|
      page.uls_switch_language("english")
      page.create_new_item(label_ab, description_a)
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label_ab, description_b)
    end
  end

  context "item disambiguation functionality test" do
    it "should search for item by label" do
      visit_page(ItemDisambiguationPage) do |page|
        page.disambiguationLanguageField = "en"
        page.disambiguationLabelField = label_ab
        page.disambiguationSubmit
        page.countDisambiguationElements.should == 2
        page.disambiguationItemLink1_element.click
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_ab
        page.itemDescriptionSpan.should == description_a
        @browser.back
        page.disambiguationItemLink2_element.click
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_ab
        page.itemDescriptionSpan.should == description_b
      end
    end
  end
  after :all do
    # tear down
  end
end
