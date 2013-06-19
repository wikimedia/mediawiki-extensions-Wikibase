# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for known bugs in statements UI

require 'spec_helper'

statement_value = generate_random_string(10) + '.jpg'

num_items = 2
num_props_cm = 2

# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

# commons media properties
count = 0
properties_cm = Array.new
while count < num_props_cm do
  properties_cm.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"Commons media file"})
  count = count + 1
end

describe "Check for bugs in statements UI" do
  before :all do
    # set up: create item & property
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
    properties_cm.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
  end

  context "Check for bugs in statements UI" do
    it "should check that save-button is disabled when property field is empty" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that save-button is disabled when no property is selected" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = properties_cm[0]['label'][0..8]
        ajax_wait
        page.wait_for_entity_selector_list
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that save-button is disabled when property value field is empty" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput =properties_cm[0]['label']
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that property-value-input gets removed when property-field gets cleared" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = properties_cm[0]['label']
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.entitySelectorInput_element.clear
        page.entitySelectorInput = " "
        page.statementValueInput?.should be_false
        page.cancelStatement
      end
    end
    it "should check that selecting an entity from the entityselector by click works" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = properties_cm[0]['label'][0..8]
        ajax_wait
        page.wait_for_entity_selector_list
        page.firstEntitySelectorLink
        ajax_wait
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.cancelStatement
      end
    end
    it "should check that save-button is disabled in edit-mode when value has not changed or is empty" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_cm[0]['label'], statement_value)
        page.editFirstStatement
        page.saveStatement?.should be_false
        page.statementValueInput_element.clear
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that cancel editmode is possible when property and statement-value fields are empty" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = properties_cm[0]['label']
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput = statement_value
        page.cancelStatement?.should be_true
        page.entitySelectorInput_element.clear
        page.entitySelectorInput = " " # element.clear seems to not trigger the correct events
        page.statementValueInput?.should be_false
        page.entitySelectorInput_element.clear
        page.cancelStatement?.should be_true
        page.cancelStatement
        page.cancelStatement?.should be_false
        page.addStatement?.should be_true
      end
    end
    it "should check that no false edit-conflicts occur" do
      on_page(ItemPage) do |page|
        page.navigate_to items[1]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_cm[0]['label'], generate_random_string(10) + '.jpg')
        page.add_statement(properties_cm[1]['label'], generate_random_string(10) + '.jpg')
        page.wait_for_entity_to_load
        page.addReferenceToFirstClaim?.should be_true
        page.addReferenceToSecondClaim?.should be_true
        page.add_reference_to_first_claim(properties_cm[0]['label'], generate_random_string(10) + '.jpg')
        page.addReferenceToSecondClaim
        page.entitySelectorInput = properties_cm[1]['label']
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_reference_value_box
        page.referenceValueInput = generate_random_string(10) + '.jpg'
        ajax_wait
        page.saveReference
        ajax_wait
        page.wbErrorDiv?.should be_false
        page.wait_for_statement_request_finished
        page.wbErrorDiv?.should be_false
      end
    end
  end

  after :all do
    # tear down
  end
end
