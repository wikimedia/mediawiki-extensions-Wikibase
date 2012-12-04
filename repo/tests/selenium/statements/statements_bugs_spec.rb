# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for known bugs in statements UI

require 'spec_helper'

item_label = generate_random_string(10)
item_description = generate_random_string(20)
prop_a_label = generate_random_string(10)
prop_a_description = generate_random_string(20)
prop_a_datatype = "Commons media file"
statement_value = generate_random_string(10)

describe "Check for bugs in statements UI" do
  before :all do
    # set up: create item & property
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label, item_description)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(prop_a_label, prop_a_description, prop_a_datatype)
    end
  end

  context "Check for bugs in statements UI" do
    it "should check that save-button is disabled when property field is empty" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that save-button is disabled when no property is selected" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_a_label[0..8]
        ajax_wait
        page.wait_for_entity_selector_list
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that save-button is disabled when property value field is empty" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_a_label
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
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_a_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.entitySelectorInput_element.clear
        page.statementValueInput?.should be_false
        page.cancelStatement
      end
    end
    it "should check that selecting an entity from the entityselector by click works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_a_label[0..8]
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
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_statement(prop_a_label, statement_value)
        page.editFirstStatement
        page.saveStatement?.should be_false
        page.statementValueInput_element.clear
        page.saveStatement?.should be_false
        page.cancelStatement
      end
    end
    it "should check that cancel editmode is possible when property and statement-value fields are empty" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_a_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput = statement_value
        page.cancelStatement?.should be_true
        page.entitySelectorInput_element.clear
        page.entitySelectorInput = " "
        page.statementValueInput_element.clear
        page.statementValueInput = " "
        page.cancelStatement?.should be_true
        page.cancelStatement
        page.cancelStatement?.should be_false
        page.addStatement?.should be_true
      end
    end
  end

  after :all do
    # tear down
  end
end
