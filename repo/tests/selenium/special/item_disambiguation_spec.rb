# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for ItemDisambiguation special page

require 'spec_helper'

label_ab = generate_random_string(10)
description_a = generate_random_string(20)
description_b = generate_random_string(20)

describe "Check item disambiguation special page" do

  context "disambiguation test setup" do
    it "should create 2 items with same label but different description" do
      visit_page(ItemPage) do |page|
        page.uls_switch_language("english")
        page.wait_for_item_to_load
        page.create_new_item(label_ab, description_a)
      end
      visit_page(ItemPage) do |page|
        page.create_new_item(label_ab, description_b)
      end
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

end
