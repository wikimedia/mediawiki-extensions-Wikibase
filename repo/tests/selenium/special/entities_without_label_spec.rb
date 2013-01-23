# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for EntitiesWithoutLabel special page

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
language_code_de = "de"
label_de = generate_random_string(10)
item_url = ""

describe "Check EntitiesWithoutLabel special page" do
  before :all do
    # set up: create item
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
    on_page(ItemPage) do |page|
      item_url = page.get_item_url
    end
  end

  context "EntitiesWithoutLabel functionality test" do
    it "should find an item through EntitiesWithoutLabel special page" do
      visit_page(EntitiesWithoutLabelPage) do |page|
        page.languageField = language_code_de
        page.entitiesWithoutLabelSubmit
        page.wait_for_entity_to_load
        page.listItemLink_element.attribute("href").should == item_url
      end
    end
    it "should add de label" do
      visit_page(ItemPage) do |page|
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.labelInputField = label_de
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
      end
    end
    it "should not find an item through EntitiesWithoutLabel special page" do
      visit_page(EntitiesWithoutLabelPage) do |page|
        page.languageField = language_code_de
        page.entitiesWithoutLabelSubmit
        page.wait_for_entity_to_load
        page.listItemLink_element.attribute("href").should_not == item_url
      end
    end
  end
end

