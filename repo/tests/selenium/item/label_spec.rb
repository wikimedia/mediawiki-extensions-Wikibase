# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item label

require 'spec_helper'

label = generate_random_string(10)
label_changed = label + " Adding something."
label_unnormalized = "  me haz   too many       spaces inside           "
label_normalized = "me haz too many spaces inside"

describe "Check functionality of edit label" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, generate_random_string(20))
    end
  end

  context "Check for edit label" do
    it "should check behavior of cancel-link" do
      on_page(ItemPage) do |page|
        page.firstHeading.should be_true
        page.entityLabelSpan.should be_true
        @browser.title.include?(label).should be_true
        page.entityLabelSpan.should == label
        page.editLabelLink?.should be_true
        page.cancelLabelLink?.should be_false
        page.editLabelLink
        page.editLabelLink?.should be_false
        page.cancelLabelLink?.should be_true
        page.saveLabelLinkDisabled?.should be_true
        page.labelInputField.should be_true
        page.labelInputField_element.clear
        page.labelInputField = label_changed
        page.saveLabelLink?.should be_true
        page.cancelLabelLink
        page.editLabelLink?.should be_true
        page.cancelLabelLink?.should be_false
        page.entityLabelSpan.should == label
      end
    end
    it "should check behavior of ESC-key" do
      on_page(ItemPage) do |page|
        page.editLabelLink
        page.labelInputField = label_changed
        page.labelInputField_element.send_keys :escape
        page.editLabelLink?.should be_true
        page.entityLabelSpan.should == label
      end
    end
    it "should check functionality of saving with save-link" do
      on_page(ItemPage) do |page|
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = label_changed
        page.saveLabelLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.entityLabelSpan.should == label_changed
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_changed
        @browser.title.include? label_changed
      end
    end
    it "should check functionality of saving with RETURN-key" do
      on_page(ItemPage) do |page|
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = label
        page.labelInputField_element.send_keys :return
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.entityLabelSpan.should == label
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        @browser.title.include? label
      end
    end
  end

  context "Check for special inputs for label" do
    it "should check if normalization for item labels is working" do
      on_page(ItemPage) do |page|
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = label_unnormalized
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.entityLabelSpan.should == label_normalized
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_normalized
      end
    end
    it "should check for correct behavior on '0'" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = "0"
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.entityLabelSpan.should == "0"
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == "0"
      end
    end
    it "should check for length constraint (assuming max 250 chars)" do
      on_page(ItemPage) do |page|
        too_long_string =
        "loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo" +
        "oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo" +
        "oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong";
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = too_long_string
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
      end
    end
  end

  after :all do
    # tear down
  end
end
