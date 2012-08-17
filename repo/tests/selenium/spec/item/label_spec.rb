# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item label

require 'spec_helper'

describe "Check functionality of edit label" do
  context "Check for edit label" do
    it "should check for edit label" do
      visit_page(NewItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))

        page.firstHeading.should be_true
        page.itemLabelSpan.should be_true
        current_label = page.itemLabelSpan
        changed_label = current_label + "_fooo"
        @browser.title.include?(current_label).should be_true
        page.itemLabelSpan.should == current_label
        page.editLabelLink?.should be_true
        page.cancelLabelLink?.should be_false
        page.editLabelLink
        page.editLabelLink?.should be_false
        page.cancelLabelLink?.should be_true
        page.saveLabelLinkDisabled?.should be_true
        page.labelInputField.should be_true
        page.labelInputField_element.clear
        page.labelInputField = changed_label
        page.saveLabelLink?.should be_true
        page.cancelLabelLink
        page.editLabelLink?.should be_true
        page.cancelLabelLink?.should be_false
        page.itemLabelSpan.should == current_label
        # checking behaviour of ESC key 
        page.editLabelLink
        page.labelInputField = changed_label
        page.labelInputField_element.send_keys :escape
        page.editLabelLink?.should be_true
        page.itemLabelSpan.should == current_label
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = changed_label
        page.saveLabelLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true

        page.itemLabelSpan.should == changed_label
        @browser.refresh
        page.wait_for_item_to_load
        page.itemLabelSpan.should == changed_label
        @browser.title.include? changed_label
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = current_label
        page.saveLabelLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true

        page.itemLabelSpan.should == current_label
        @browser.refresh
        page.wait_for_item_to_load
        page.itemLabelSpan.should == current_label
        @browser.title.include? current_label
      end
    end
  end

  context "Check for normalization of label" do
    it "should check if normalization for item labels is working" do
      on_page(NewItemPage) do |page|
        label_unnormalized = "  me haz   too many       spaces inside           "
        label_normalized = "me haz too many spaces inside"
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = label_unnormalized
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.itemLabelSpan.should == label_normalized
        @browser.refresh
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_normalized
      end
    end
  end
end
