# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item type statements

require 'spec_helper'

num_items = 2
num_props_item = 1

# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

# item properties
count = 0
properties_item = Array.new
while count < num_props_item do
  properties_item.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"Item"})
  count = count + 1
end

describe "Check item type statements UI" do
  before :all do
    # set up: create items & properties
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
    properties_item.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
  end

  context "Check statements UI" do
    it "should check adding a statement of item type" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_item[0]["label"], items[1]["label"])
        page.statement1Name.should == properties_item[0]["label"]
        page.statement1ClaimValue1.should == items[1]["label"]
        page.statement1ClaimValue1_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == items[1]["label"]
        @browser.back
        @browser.refresh
        page.wait_for_entity_to_load
        page.statement1ClaimValue1_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == items[1]["label"]
      end
    end
    it "should check editing a statement of item type" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        ajax_wait
        page.statementValueInput.should == items[1]["label"]
        page.cancelStatement
      end
    end
    it "should check handling of item & property with no label" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.uls_switch_language("de", "Deutsch") # switch to german, no label for the item & the property should be set there
        page.wait_for_entity_to_load
        page.statement1Name.include?(properties_item[0]["id"]).should be_true
        page.statement1ClaimValue1.include?(items[1]["id"]).should be_true
        page.statement1ClaimValue1_element.click
        page.wait_for_entity_to_load
        @browser.title.include?(ITEM_ID_PREFIX + items[1]["id"]).should be_true
        @browser.back
        @browser.refresh
        page.wait_for_entity_to_load
        page.statement1Name_element.click
        page.wait_for_entity_to_load
        @browser.title.include?(PROPERTY_ID_PREFIX + properties_item[0]["id"]).should be_true
      end
    end
  end

  after :all do
    # tear down: switch to default language again
    on_page(ItemPage) do |page|
      page.uls_switch_language(LANGUAGE_CODE, LANGUAGE_NAME)
    end
  end

end