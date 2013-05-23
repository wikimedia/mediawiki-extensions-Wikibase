# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for known bugs

require 'spec_helper'

label_1 = generate_random_string(10)
label_2 = generate_random_string(10)
description_1 = generate_random_string(20)
description_2 = generate_random_string(20)
description_en = "english"
description_de = "deutsch"

describe "Check for known bugs" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), description_en)
    end
  end

  context "description and aliases appear in wrong languages" do
    it "should check if the bug exists" do
      on_page(ItemPage) do |page|
        page.navigate_to_item_en
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_en
        page.navigate_to_item_de
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should_not == description_en
        page.descriptionInputField_element.clear
        page.descriptionInputField = description_de
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.navigate_to_item_en
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_en
        page.navigate_to_item_de
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_de
      end
    end
  end

  context "label/description uniqueness constraint" do
    it "should check error reporting when changing label/description (bug 43301)" do
      item_1 = 0
      item_2 = 0
      visit_page(CreateItemPage) do |page|
        item_1 = page.create_new_item(label_1, description_1)
      end
      visit_page(CreateItemPage) do |page|
        item_2 = page.create_new_item(label_2, description_1)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_2
        page.change_label(label_1)
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wait_for_error_details
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.include?(label_1).should be_true
        page.wbErrorDetailsDiv_element.text.include?(description_1).should be_true
        page.wbErrorDetailsDiv_element.text.include?(item_1).should be_true
        @browser.refresh
        page.wait_for_entity_to_load
        page.change_description(description_2)
        page.wbErrorDiv?.should be_false
        page.change_label(label_1)
        page.wbErrorDiv?.should be_false
        page.change_description(description_1)
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wait_for_error_details
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.include?(label_1).should be_true
        page.wbErrorDetailsDiv_element.text.include?(description_1).should be_true
        page.wbErrorDetailsDiv_element.text.include?(item_1).should be_true
      end
    end
  end
  after :all do
    # tear down
  end
end
