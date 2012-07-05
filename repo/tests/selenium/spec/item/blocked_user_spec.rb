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
      visit_page(NewItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_USERNAME, WIKI_PASSWORD)
      end
      visit_page(BlockUserPage) do |page|
        page.block_user(WIKI_BLOCKED_USERNAME, "1 hour")
      end
    end
  end
  context "check functionality of blocking a user" do
    it "should login as blocked user and check if he cannot edit an item" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      on_page(NewItemPage) do |page|
        page.navigate_to_item
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
