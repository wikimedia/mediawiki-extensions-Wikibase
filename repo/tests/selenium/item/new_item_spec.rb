# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for new item special page

require 'spec_helper'

describe "Check functionality of create new item" do

  context "Check for create new item" do
    it "should check for functionality of create new item" do
      initial_label = generate_random_string(10)
      initial_description = generate_random_string(20)

      visit_page(ItemPage) do |page|
        page.wait_for_item_to_load
        page.labelInputField.should be_true
        page.descriptionInputField.should be_true
        page.create_new_item(initial_label, initial_description)
        page.itemLabelSpan.should == initial_label
        page.itemDescriptionSpan.should == initial_description
      end
    end
  end

  context "Check for correct redirect on create new item" do
    it "should check that the redirect preserves the correct uselang parameter" do
      initial_label = generate_random_string(10)
      visit_page(ItemPage) do |page|
        page.wait_for_item_to_load
      end
      on_page(ItemPage) do |page|
        page.uls_switch_language("Deutsch")
        page.wait_for_item_to_load
        page.labelInputField= initial_label
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.wait_for_new_item_creation
        page.wait_for_item_to_load
        page.itemLabelSpan.should == initial_label
        page.viewTabLink_element.text == "Lesen"
      end
    end
  end
end

