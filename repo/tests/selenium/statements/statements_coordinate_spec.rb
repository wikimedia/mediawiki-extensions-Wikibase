# coding: UTF-8
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
coordinate_values = Array.new
coordinate_values.push({
  #"input" => '41' + '&deg;'.html.html_safe + '50\'13"N, 87' + '&deg;'.html.html_safe + '41\'4.444"W',
  "input" => "41\u00B050'13\"N, 87°41'4.444\"W".force_encoding("UTF-8"),
  "precision" => "auto",
  "expected_precision" => "to an arcsecond",
  "expected_preview" => '41°50\'13"N, 87°41\'4.444"W'
})

describe "Check coordinate statements UI" do
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
        page.coordinateInputExtender_element.when_visible
        page.coordinateInputExtender_element.visible?.should be_true
        page.coordinateInputExtenderClose_element.click
        page.coordinateInputExtender_element.when_not_visible
        page.coordinateInputExtender_element.visible?.should be_false
        page.statementValueInputField_element.click
        page.coordinateInputExtender_element.when_visible
        page.coordinateInputExtender_element.visible?.should be_true
        page.coordinatePreviewValue_element.visible?.should be_true
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
=begin
    time_values.each do |time|
      it "should check preview for '" + time["input"] + "'/" + time["precision"] + '/' + time["calendar"] do
        on_page(ItemPage) do |page|
          page.navigate_to_item
          page.wait_for_entity_to_load
          page.addStatement
          page.entitySelectorInput = prop_label
          ajax_wait
          page.wait_for_entity_selector_list
          page.wait_for_property_value_box
          page.statementValueInputField_element.clear
          page.statementValueInputField = time["input"]
          page.timeInputExtender_element.when_visible
          page.select_precision time["precision"]
          page.select_calendar time["calendar"]
          page.timePreviewValue.should == time["expected_preview"]
          page.timePrecisionRotatorSelect_element.text.should == time["expected_precision"]
          if time["expected_calendarhint"] == ""
            page.timeCalendarHint_element.visible?.should be_false
          else
            page.timeCalendarHint_element.visible?.should be_true
            page.timeCalendarHintMessage.should == time["expected_calendarhint"]
          end
          page.cancelStatement
        end
      end
    end

    it "should check saving of time" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInputField = time_values[0]["input"]
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        page.statement1ClaimValue1Nolink.should == time_values[0]["expected_preview"]
        @browser.refresh
        page.wait_for_entity_to_load
        page.statement1ClaimValue1Nolink.should == time_values[0]["expected_preview"]
      end
    end
=end
  end

  after :all do
    # tear down
  end

end