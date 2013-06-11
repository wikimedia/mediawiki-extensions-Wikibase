# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for time type statements

require 'spec_helper'

item_label = generate_random_string(10)
item_description = generate_random_string(20)
prop_label = generate_random_string(10)
prop_description = generate_random_string(20)
prop_datatype = "Time"
time_values = ["1 1 1"]

describe "Check time statements UI" do
  before :all do
    # set up: create item & properties
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label, item_description)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(prop_label, prop_description, prop_datatype)
    end
  end

  context "Check time UI" do
    it "should check time input extender behaviour" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInputField = time_values[0]
        page.timeInputExtender_element.when_visible
        page.timeInputExtender_element.visible?.should be_true
        page.timeInputExtenderClose_element.click
        page.timeInputExtender_element.when_not_visible
        page.timeInputExtender_element.visible?.should be_false
        page.statementValueInputField_element.click
        page.timeInputExtender_element.when_visible
        page.timeInputExtender_element.visible?.should be_true
        page.timePreviewValue_element.visible?.should be_true
        page.timeInputExtenderAdvanced_element.visible?.should be_true
      end
    end
  end

  after :all do
    # tear down
  end

end