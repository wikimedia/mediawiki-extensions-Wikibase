# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item description

require 'spec_helper'

description = generate_random_string(20)
description_changed = description + " Adding something."
description_unnormalized = "  me haz   too many       spaces inside           "
description_normalized = "me haz too many spaces inside"

describe "Check functionality of edit description" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), description)
    end
  end

  context "Check for item description UI" do
    it "should check behaviour of cancel-link" do
      on_page(ItemPage) do |page|
        page.itemDescriptionSpan.should be_true
        page.itemDescriptionSpan.should == description
        page.wait_for_item_to_load
        page.editDescriptionLink?.should be_true
        page.cancelDescriptionLink?.should be_false
        page.editDescriptionLink
        page.editDescriptionLink?.should be_false
        page.cancelDescriptionLink?.should be_true
        page.saveDescriptionLinkDisabled?.should be_true
        page.descriptionInputField.should be_true
        page.descriptionInputField_element.clear
        page.descriptionInputField = description_changed
        page.saveDescriptionLink?.should be_true
        page.cancelDescriptionLink
        page.editDescriptionLink?.should be_true
        page.cancelDescriptionLink?.should be_false
        page.itemDescriptionSpan.should == description
      end
    end
    it "should check behaviour of ESC-key" do
      on_page(ItemPage) do |page|
        page.editDescriptionLink
        page.descriptionInputField = description_changed
        page.descriptionInputField_element.send_keys :escape
        page.editDescriptionLink?.should be_true
        page.itemDescriptionSpan.should == description
      end
    end
    it "should check functionality of saving description with save-link" do
      on_page(ItemPage) do |page|
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField = description_changed
        page.saveDescriptionLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == description_changed
        page.editDescriptionLink?.should be_true
        @browser.refresh
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == description_changed
      end
    end
    it "should check functionality of saving the description with RETURN-key" do
      on_page(ItemPage) do |page|
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField = description
        page.descriptionInputField_element.send_keys :return
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == description
        page.editDescriptionLink?.should be_true
        @browser.refresh
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == description
      end
    end
  end

  context "Check for normalization of description" do
    it "should check if normalization for item description is working" do
      on_page(ItemPage) do |page|
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

  after :all do
    # tear down
  end
end

