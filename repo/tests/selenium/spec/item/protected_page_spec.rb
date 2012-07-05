# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for a protected page

require 'spec_helper'

describe "Check functionality of protected page" do
  context "create item, protect page" do
    it "should create an item, login with admin and protect the page, then logout the admin again" do
      visit_page(NewItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_USERNAME, WIKI_PASSWORD)
      end
      on_page(ProtectedPage) do |page|
        page.protect_page
      end
      visit_page(LoginPage) do |page|
        page.logout
      end
    end
  end

  context "check functionality of protected page" do
    it "should be logged out, and check if label/description of protected item could not be edited" do
      visit_page(LoginPage) do |page|
        if page.logout? == true
          page.logout_user
        end
      end
      on_page(NewItemPage) do |page|
        page.navigate_to_item
        #label
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

  context "check functionality of protected page" do
    it "check if no aliases could be added to an item" do
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

  context "unprotect page" do
    it "should unprotect the page and logout" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_USERNAME, WIKI_PASSWORD)
      end
      visit_page(ProtectedPage) do |page|
        page.unprotect_page
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end
end
