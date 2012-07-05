# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for a blocked user

require 'spec_helper'

describe "Check functionality of blocking a user" do
  context "create item, block user" do
    it "should create an item and block a user" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_USERNAME, WIKI_PASSWORD)
      end
      visit_page(NewItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      visit_page(BlockUserPage) do |page|
        page.block_user(WIKI_BLOCKED_USERNAME, "1 hour")
      end
    end
  end

  context "check functionality of blocking a user" do
    it "should login as blocked user and check if he cannot edit label/description" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      on_page(NewItemPage) do |page|
        page.navigate_to_item
        # label
        original_label = page.itemLabelSpan
        changed_label = original_label + "123"
        page.editLabelLink
        page.labelInputField_element.clear
        page.labelInputField = changed_label
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDiv_element.text.should == "You are not allowed to perform this action."
        @browser.refresh
        page.wait_for_item_to_load
        page.itemLabelSpan.should == original_label
        # description
        original_description = page.itemDescriptionSpan
        changed_description = original_description + "123"
        page.editDescriptionLink
        page.descriptionInputField_element.clear
        page.descriptionInputField = changed_description
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDiv_element.text.should == "You are not allowed to perform this action."
        @browser.refresh
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == original_description
      end
    end
  end

  context "check functionality of blocking a user" do
    it "should login as blocked user and check if he cannot add aliases" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        new_alias = generate_random_string(5);
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.aliasesInputEmpty= new_alias
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDiv_element.text.should == "You are not allowed to perform this action."
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases?.should be_true
      end
    end
  end

  context "check functionality of blocking a user" do
    it "should login as blocked user and check he is blocked from item creation" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      visit_page(NewItemPage) do |page|
        page.wait_for_item_to_load
        page.labelInputField = "I am not allowed to do that!"
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDiv_element.text.should == "You are not allowed to perform this action."
        page.descriptionInputField = "I am also not allowed to do that!"
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDiv_element.text.should == "You are not allowed to perform this action."
      end
    end
  end

  context "unblock user" do
    it "should unblock the user and logout" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_USERNAME, WIKI_PASSWORD)
      end
      visit_page(UnblockUserPage) do |page|
        page.unblock_user(WIKI_BLOCKED_USERNAME)
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end
end
