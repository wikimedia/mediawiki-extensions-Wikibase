# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item description

require 'spec_helper'

describe "Check functionality of edit description" do

  context "Check for item description UI" do
    it "should check for edit description" do
      visit_page(ItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))

        page.itemDescriptionSpan.should be_true
        current_description = page.itemDescriptionSpan
        changed_description = current_description + " Adding something."
        page.itemDescriptionSpan.should == current_description
        page.wait_for_item_to_load
        page.editDescriptionLink?.should be_true
        page.cancelDescriptionLink?.should be_false
        page.editDescriptionLink
        page.editDescriptionLink?.should be_false
        page.cancelDescriptionLink?.should be_true
        page.saveDescriptionLinkDisabled?.should be_true
        page.descriptionInputField.should be_true
        page.descriptionInputField_element.clear
        page.descriptionInputField = changed_description
        page.saveDescriptionLink?.should be_true
        page.cancelDescriptionLink
        page.editDescriptionLink?.should be_true
        page.cancelDescriptionLink?.should be_false
        page.itemDescriptionSpan.should == current_description
        # checking behaviour of ESC key
        page.editDescriptionLink
        page.descriptionInputField = changed_description
        page.descriptionInputField_element.send_keys :escape
        page.editDescriptionLink?.should be_true
        page.itemDescriptionSpan.should == current_description
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField = changed_description
        page.saveDescriptionLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == changed_description
        page.editDescriptionLink?.should be_true

        @browser.refresh
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == changed_description
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField = current_description
        page.saveDescriptionLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == current_description
        page.editDescriptionLink?.should be_true

        @browser.refresh
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == current_description
      end
    end

    context "Check for normalization of description" do
      it "should check if normalization for item description is working" do
        on_page(ItemPage) do |page|
          description_unnormalized = "  me haz   too many       spaces inside           "
          description_normalized = "me haz too many spaces inside"
          page.editDescriptionLink
          page.descriptionInputField_element.clear
          page.descriptionInputField = description_unnormalized
          page.saveDescriptionLink
          ajax_wait
          page.wait_for_api_callback
          page.itemDescriptionSpan.should == description_normalized
          @browser.refresh
          page.wait_for_item_to_load
          page.itemDescriptionSpan.should == description_normalized
        end
      end
    end
  end

end

