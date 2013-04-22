# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for autocomments

require 'spec_helper'

num_items = 2
num_props_string = 3
#num_props_item = 1

# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

# commons media properties
count = 0
properties_string = Array.new
string_values = Array.new
while count < num_props_string do
  properties_string.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"String"})
  string_values.push({"value"=>generate_random_string(10), "changed_value"=>generate_random_string(10)})
  count = count + 1
end

# item properties
#count = 0
#properties_item = Array.new
#while count < num_props_item do
#  properties_item.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"Item"})
#  count = count + 1
#end

describe "Check AC/AS" do
  before :all do
    # set up: create items & properties
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
    properties_string.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
  end

  context "autocomments/autosummaries setClaim" do
    it "should check add claim AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_string[0]["label"], string_values[0]["value"])
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Created claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
    it "should check add one qualifier AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_qualifier_to_first_claim(properties_string[1]["label"], string_values[1]["value"])
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed one qualifier of claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
    it "should check edit one qualifier AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.qualifierValueInput1_element.clear
        page.qualifierValueInput1 = string_values[1]["changed_value"]
        ajax_wait
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed 2 qualifiers of claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
    it "should check remove one qualifier AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.removeQualifierLine1
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed one qualifier of claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
    it "should check add two qualifiers AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.addQualifier
        page.entitySelectorInput = properties_string[1]["label"]
        ajax_wait
        page.wait_for_qualifier_value_box
        page.qualifierValueInput1 = string_values[1]["value"]
        ajax_wait
        page.addQualifier
        page.entitySelectorInput2 = properties_string[2]["label"]
        ajax_wait
        page.wait_for_qualifier_value_box
        page.qualifierValueInput2 = string_values[2]["value"]
        ajax_wait
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed 2 qualifiers of claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
    it "should check edit claim AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.edit_first_statement(string_values[0]["changed_value"])
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["changed_value"]).should == true
      end
    end
    it "should check edit claim & two qualifiers AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.statementValueInput_element.clear
        page.statementValueInput = string_values[0]["value"]
        ajax_wait
        page.qualifierValueInput1_element.clear
        page.qualifierValueInput1 = string_values[1]["changed_value"]
        ajax_wait
        page.qualifierValueInput2_element.clear
        page.qualifierValueInput2 = string_values[2]["changed_value"]
        ajax_wait
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
    it "should check remove two qualifiers AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.removeQualifierLine2
        page.removeQualifierLine1
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Changed 2 qualifiers of claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
  end
  context "autocomments/autosummaries removeClaim" do
    it "should check remove claim AC/AS" do
      on_page(ItemPage) do |page|
        page.navigate_to items[1]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_string[0]["label"], string_values[0]["value"])
        page.editFirstStatement
        page.removeClaimButton
        ajax_wait
        page.wait_for_statement_request_finished
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.autocomment1.include?("Removed claim:").should == true
        page.autosummary1.include?("Property:" + PROPERTY_ID_PREFIX + properties_string[0]["id"] + ": " + string_values[0]["value"]).should == true
      end
    end
  end

  after :all do
    # tear down
  end
end
