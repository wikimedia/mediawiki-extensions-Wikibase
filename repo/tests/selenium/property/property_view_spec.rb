# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for property view

require 'spec_helper'

label = generate_random_string(10)
label_changed = label + " stuff"
description = generate_random_string(20)
description_changed = description + " stuff"

describe "Check functionality of property view" do
  before :all do
    # set up: create property
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(label, description)
    end
  end

  context "Check for editing property label/description" do
    it "should check changing label" do
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        @browser.title.include?(label).should be_true
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
        page.editLabelLink
        page.labelInputField = label_changed
        page.saveLabelLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.entityLabelSpan.should == label_changed
        page.entityDescriptionSpan.should == description
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_changed
        page.entityDescriptionSpan.should == description
        @browser.title.include? label_changed
        page.editLabelLink
        page.labelInputField = label
        page.saveLabelLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
      end
    end
    it "should check changing description" do
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
        page.editDescriptionLink
        page.descriptionInputField = description_changed
        page.saveDescriptionLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
        page.editDescriptionLink?.should be_true
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description_changed
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description_changed
        page.editDescriptionLink
        page.descriptionInputField = description
        page.saveDescriptionLink
        page.apiCallWaitingMessage?.should be_true
        ajax_wait
        page.wait_for_api_callback
      end
    end
    it "should check behavior when using '0' as label/description" do
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = '0'
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField = '0'
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.entityLabelSpan.should == '0'
        page.entityDescriptionSpan.should == '0'
        @browser.refresh
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == '0'
        page.entityDescriptionSpan.should == '0'
      end
    end
  end

  context "Check for adding/removing property aliases" do
    it "should check that adding some aliases work properly" do
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.addAliases
        i = 0;
        while i < 3 do
          page.aliasesInputEmpty = generate_random_string(8)
          i += 1;
        end
        page.aliasesInputEmpty = '0'
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == 4
      end
    end
    it "should check that removing aliases work properly" do
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.editAliases
        page.aliasesInputFirstRemove?.should be_true
        num_aliases = page.count_existing_aliases
        i = 0;
        while i < (num_aliases-1) do
          page.aliasesInputFirstRemove?.should be_true
          page.aliasesInputFirstRemove
          i += 1;
        end
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.addAliases?.should be_true
      end
    end
  end

  context "Check for data type" do
    it "should check that the data type is displayed correctly" do
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.datatype.include? "Item"
      end
    end
  end

  after :all do
    # tear down: set label/description to something random (to not violate unique constraints in future tests)
    on_page(PropertyPage) do |page|
      page.navigate_to_property
      page.wait_for_entity_to_load
      page.editLabelLink
      page.labelInputField_element.clear
      page.labelInputField = generate_random_string(10)
      page.saveLabelLink
      ajax_wait
      page.wait_for_api_callback
      page.editDescriptionLink
      page.descriptionInputField_element.clear
      page.descriptionInputField = generate_random_string(20)
      page.saveDescriptionLink
      ajax_wait
      page.wait_for_api_callback
    end
  end
end
