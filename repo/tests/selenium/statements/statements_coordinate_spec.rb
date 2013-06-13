# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for coordinate type statements

require 'spec_helper'

item_label = generate_random_string(10)
item_description = generate_random_string(20)
prop_label = generate_random_string(10)
prop_description = generate_random_string(20)
prop_datatype = "Geographic coordinate"
not_recognized = "no valid value recognized"
# utf8 code for ° sign: \u00B0
coordinate_values = Array.new
coordinate_values.push({
  "input" => "41°50'13\"N, 87°41'44\"W",
  "precision" => "auto",
  "expected_precision" => "to an arcsecond",
  "expected_preview" => "41°50'13\"N, 87°41'44\"W"
})
coordinate_values.push({
  "input" => "41°50'N, 87°41'W",
  "precision" => "auto",
  "expected_precision" => "to an arcminute",
  "expected_preview" => "41°50'N, 87°41'W"
})
coordinate_values.push({
  "input" => "41°50'13\"N, 87°41'44\"W",
  "precision" => "to an arcminute",
  "expected_precision" => "to an arcminute",
  "expected_preview" => "41°50'N, 87°41'W"
})
coordinate_values.push({
  "input" => "42.1538 8.5731",
  "precision" => "auto",
  "expected_precision" => "±0.0001°",
  "expected_preview" => "42°9'13.7\"N, 8°34'23.2\"E"
})
#this is not supported by the frontend parser!
#coordinate_values.push({
#  "input" => "42° 09.231 N 008° 34.386 E",
#  "precision" => "auto",
#  "expected_precision" => "to 1/100 of an arcsecond",
#  "expected_preview" => "42°9'13.86\"N, 8°34'23.16\"E"
#})
coordinate_values.push({
  "input" => "stuff",
  "precision" => "auto",
  "expected_precision" => "to a degree",
  "expected_preview" => not_recognized
})

describe "Check coordinate statements UI", :exclude_chrome => true do
  before :all do
    # set up: create item & properties
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label, item_description)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(prop_label, prop_description, prop_datatype)
    end
  end

  context "Check coordinate UI" do
    it "should check coordinate input extender behaviour" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInputField = coordinate_values[0]["input"]
        page.inputExtender_element.when_visible
        page.inputExtender_element.visible?.should be_true
        page.inputExtenderClose_element.click
        page.inputExtender_element.when_not_visible
        page.inputExtender_element.visible?.should be_false
        page.statementValueInputField_element.click
        page.inputExtender_element.when_visible
        page.inputExtender_element.visible?.should be_true
        page.inputPreviewValue_element.visible?.should be_true
        page.coordinateInputExtenderAdvanced_element.visible?.should be_true
        page.coordinateInputExtenderAdvanced
        page.coordinatePrecision_element.when_visible
        page.coordinatePrecision_element.visible?.should be_true
        page.coordinatePrecisionRotatorSelect?.should be_true
        page.coordinateInputExtenderAdvanced
        page.coordinatePrecision_element.when_not_visible
        page.coordinatePrecision_element.visible?.should be_false
        page.coordinateInputExtenderAdvanced
        page.coordinatePrecision_element.when_visible
        page.coordinatePrecisionRotatorSelect
        page.coordinatePrecisionMenu_element.when_visible
        page.coordinatePrecisionMenu_element.visible?.should be_true
        page.coordinatePrecisionRotatorSelect
        page.coordinatePrecisionMenu_element.when_not_visible
        page.coordinatePrecisionMenu_element.visible?.should be_false
      end
    end

    coordinate_values.each do |coordinate|
      it "should check preview for '" + coordinate["input"] + "'/" + coordinate["precision"] do
        on_page(ItemPage) do |page|
          page.navigate_to_item
          page.wait_for_entity_to_load
          page.addStatement
          page.entitySelectorInput = prop_label
          ajax_wait
          page.wait_for_entity_selector_list
          page.wait_for_property_value_box
          page.statementValueInputField_element.clear
          page.statementValueInputField = coordinate["input"]
          page.inputExtender_element.when_visible
          page.select_coordinate_precision coordinate["precision"]
          page.inputPreviewValue.should == coordinate["expected_preview"]
          page.coordinatePrecisionRotatorSelect_element.text.should == coordinate["expected_precision"]
          page.cancelStatement
        end
      end
    end

    it "should check saving of coordinate" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInputField = coordinate_values[0]["input"]
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        page.statement1ClaimValue1Nolink.should == coordinate_values[0]["expected_preview"]
        @browser.refresh
        page.wait_for_entity_to_load
        page.statement1ClaimValue1Nolink.should == coordinate_values[0]["expected_preview"]
      end
    end
  end

  after :all do
    # tear down
  end

end