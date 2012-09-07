# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for editing story: disabling/enabling edit actions

require 'spec_helper'

describe "Check functionality of disabling/enabling edit actions" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), '')
    end
  end

  context "disabling/enabling of edit actions while editing label" do
    it "should check if edit actions are disbled/enabled correctly when editing label" do
      on_page(ItemPage) do |page|
        page.wait_for_item_to_load
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_false # because it's empty
        page.descriptionInputField_element.enabled?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.editLabelLink
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.descriptionInputField_element.enabled?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.cancelLabelLink?.should be_true
        page.cancelLabelLink
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_false # because it's empty
        page.descriptionInputField_element.enabled?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.editLabelLink
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.descriptionInputField_element.enabled?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.labelInputField_element.clear
        page.labelInputField_element.click
        page.descriptionInputField_element.enabled?.should be_false
        page.labelInputField= generate_random_string(10)
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_false # because it's empty
        page.descriptionInputField_element.enabled?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
      end
    end
  end

  context "disabling/enabling of edit actions while editing description" do
    it "should check if edit actions are disbled/enabled correctly when editing description" do
      on_page(ItemPage) do |page|
        page.wait_for_item_to_load
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_false # because it's empty
        page.descriptionInputField_element.enabled?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.descriptionInputField= generate_random_string(20)
        page.descriptionInputField_element.clear
        page.descriptionInputField_element.click
        page.editLabelLink?.should be_true
        page.descriptionInputField_element.enabled?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.descriptionInputField= generate_random_string(20)
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField_element.click
        page.editLabelLink?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.cancelDescriptionLink
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
      end
    end
  end

  context "disabling/enabling of edit actions while editing sitelinks" do
    it "should check if edit actions are disbled/enabled correctly when editing sitelinks" do
      on_page(ItemPage) do |page|
        page.wait_for_sitelinks_to_load
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.addSitelinkLink
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="en"
        page.pageInputField_element.enabled?.should be_true
        page.pageInputField="Germany"
        page.saveSitelinkLink?.should be_true
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
        page.wait_for_editLabelLink
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.editSitelinkLink
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.cancelSitelinkLink
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
      end
    end
  end

  context "disabling/enabling of edit actions while editing aliases" do
    it "should check if edit actions are disbled/enabled correctly when editing aliases" do
      on_page(ItemPage) do |page|
        page.wait_for_aliases_to_load
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.addAliases
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.cancelAliases
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.addAliases
        page.aliasesInputEmpty= generate_random_string(8)
        page.aliasesInputEmpty= generate_random_string(8)
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.editSitelinkLink?.should be_false
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.editAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.editLabelLink
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.editAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.cancelLabelLink
      end
    end
  end

  context "disabling/enabling of edit actions while removing sitelinks" do
    it "should check if edit actions are disbled/enabled correctly when removing sitelinks" do
      on_page(ItemPage) do |page|
        page.wait_for_sitelinks_to_load
        page.removeSitelinkLink?.should be_true
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
        page.removeSitelinkLink
        ajax_wait
        page.wait_for_api_callback
        page.editLabelLink?.should be_true
        page.editDescriptionLink?.should be_true
        page.addAliases?.should be_true
        page.addSitelinkLink?.should be_true
      end
    end
  end

  after :all do
    # tear down
  end
end
