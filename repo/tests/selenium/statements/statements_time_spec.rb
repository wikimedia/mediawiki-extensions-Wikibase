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
not_recognized = "no valid value recognized"
time_values = Array.new
time_values.push({
  "input" => "1 1 1",
  "precision" => "auto",
  "calendar" => "auto",
  "expected_precision" => "day",
  "expected_preview" => "January 1, 1",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "12.11.1981",
  "precision" => "auto",
  "calendar" => "auto",
  "expected_precision" => "day",
  "expected_preview" => "December 11, 1981",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "12.11.1929",
  "precision" => "auto",
  "calendar" => "auto",
  "expected_precision" => "day",
  "expected_preview" => "December 11, 1929",
  "expected_calendarhint" => "(Gregorian calendar)"
})
time_values.push({
  "input" => "5.20.1582",
  "precision" => "auto",
  "calendar" => "auto",
  "expected_precision" => "day",
  "expected_preview" => "May 20, 1582",
  "expected_calendarhint" => "(Julian calendar)"
})
time_values.push({
  "input" => "5.20.2000",
  "precision" => "month",
  "calendar" => "auto",
  "expected_precision" => "month",
  "expected_preview" => "May 2000",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "5 2000",
  "precision" => "auto",
  "calendar" => "auto",
  "expected_precision" => "month",
  "expected_preview" => "May 2000",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "5.20.2000",
  "precision" => "year",
  "calendar" => "auto",
  "expected_precision" => "year",
  "expected_preview" => "2000",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "19.12.1981",
  "precision" => "century",
  "calendar" => "auto",
  "expected_precision" => "century",
  "expected_preview" => "20. century",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "19.12.1981",
  "precision" => "decade",
  "calendar" => "auto",
  "expected_precision" => "decade",
  "expected_preview" => "1980s",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "19.12.1981",
  "precision" => "million years",
  "calendar" => "auto",
  "expected_precision" => "million years",
  "expected_preview" => "in 1 million years",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "19.12.1981",
  "precision" => "century",
  "calendar" => "auto",
  "expected_precision" => "century",
  "expected_preview" => "20. century",
  "expected_calendarhint" => ""
})
time_values.push({
  "input" => "April 12 1900",
  "precision" => "auto",
  "calendar" => "Julian",
  "expected_precision" => "day",
  "expected_preview" => "April 12, 1900",
  "expected_calendarhint" => "(Julian calendar)"
})
time_values.push({
  "input" => "stuff",
  "precision" => "auto",
  "calendar" => "auto",
  "expected_precision" => "day",
  "expected_preview" => not_recognized,
  "expected_calendarhint" => ""
})

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
        page.statementValueInputField = time_values[0]["input"]
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
        page.timeInputExtenderAdvanced
        page.timePrecision_element.when_visible
        page.timePrecision_element.visible?.should be_true
        page.timeCalendar_element.visible?.should be_true
        page.timePrecisionRotatorSelect?.should be_true
        page.timeCalendarRotatorSelect?.should be_true
        page.timeInputExtenderAdvanced
        page.timePrecision_element.when_not_visible
        page.timePrecision_element.visible?.should be_false
        page.timeCalendar_element.visible?.should be_false
        page.timeInputExtenderAdvanced
        page.timePrecision_element.when_visible
        page.timePrecisionRotatorSelect
        page.timePrecisionMenu_element.when_visible
        page.timePrecisionMenu_element.visible?.should be_true
        page.timePrecisionRotatorSelect
        page.timePrecisionMenu_element.when_not_visible
        page.timePrecisionMenu_element.visible?.should be_false
        page.timeCalendarRotatorSelect
        page.timeCalendarMenu_element.when_visible
        page.timeCalendarMenu_element.visible?.should be_true
        page.timeCalendarRotatorSelect
        page.timeCalendarMenu_element.when_not_visible
        page.timeCalendarMenu_element.visible?.should be_false
      end
    end

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
  end

  after :all do
    # tear down
  end

end