# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# Author:: H. Snater
# License:: GNU GPL v2+
#
# tests for a blocked user

require 'spec_helper'

describe "Check functionality of blocking a user" do
  context "create item, block user" do
    it "should create an item and block a user" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      visit_page(ItemPage) do |page|
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
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        #label
        page.editLabelLink?.should be_false
        page.editLabelLinkDisabled?.should be_true
        page.editLabelLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.labelInputField?.should be_false
        page.itemLabelSpan_element.click
        # description
        page.editDescriptionLink?.should be_false
        page.editDescriptionLinkDisabled?.should be_true
        page.editDescriptionLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.descriptionInputField?.should be_false
      end
    end
  end

  context "check functionality of blocking a user" do
    it "should login as blocked user and check if he cannot add aliases" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases?.should be_false
        page.addAliasesDisabled?.should be_true
        page.addAliasesDisabled_element.click
        page.wbTooltip?.should be_true
        page.aliasesInputEmpty?.should be_false
        page.saveAliases?.should be_false
      end
    end
  end

  context "check functionality of blocking a user" do
    it "should login as blocked user and check he is blocked from item creation" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      visit_page(ItemPage) do |page|
        page.wait_for_item_to_load
        page.labelInputField_element.enabled?.should be_false
        page.saveLabelLink?.should be_false
        page.saveLabelLinkDisabled?.should be_true
        page.saveLabelLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.descriptionInputField_element.enabled?.should be_false
        page.saveDescriptionLink?.should be_false
        page.saveDescriptionLinkDisabled?.should be_true
        page.saveDescriptionLinkDisabled_element.click
        page.wait_until do
          page.wbTooltip?
        end
        page.wbTooltip?.should be_true
      end
    end
  end

  context "unblock user" do
    it "should unblock the user and logout" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
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
