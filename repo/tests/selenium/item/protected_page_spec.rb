# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# Author:: H. Snater
# License:: GNU GPL v2+
#
# tests for a protected page

require 'spec_helper'

describe "Check functionality of protected page" do
  before :all do
    # set up: create item, login as admin, protect page, logout
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), generate_random_string(20))
    end
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    on_page(ProtectedPage) do |page|
      page.protect_page
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end

  context "check functionality of protected page" do
    it "should be logged in with normal user, and check if label/description of protected item could not be edited" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        #label
        page.editLabelLink?.should be_false
        page.editLabelLinkDisabled?.should be_true
        page.editLabelLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.labelInputField?.should be_false
        page.entityLabelSpan_element.click
        # description
        page.editDescriptionLink?.should be_false
        page.editDescriptionLinkDisabled?.should be_true
        page.editDescriptionLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.descriptionInputField?.should be_false
      end
    end
  end

  context "check functionality of protected page" do
    it "should be logged out, and check if label/description of protected item could not be edited" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        #label
        page.editLabelLink?.should be_false
        page.editLabelLinkDisabled?.should be_true
        page.editLabelLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.labelInputField?.should be_false
        page.entityLabelSpan_element.click
        # description
        page.editDescriptionLink?.should be_false
        page.editDescriptionLinkDisabled?.should be_true
        page.editDescriptionLinkDisabled_element.click
        page.wbTooltip?.should be_true
        page.descriptionInputField?.should be_false
      end
    end
  end

  context "check functionality of protected page" do
    it "check if no aliases could be added to an item" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addAliases?.should be_false
        page.addAliasesDisabled?.should be_true
        page.addAliasesDisabled_element.click
        page.wbTooltip?.should be_true
        page.aliasesInputEmpty?.should be_false
        page.saveAliases?.should be_false
      end
    end
  end

  after :all do
    # tear down: login as admin, unprotect page, logout
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    visit_page(ProtectedPage) do |page|
      page.unprotect_page
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
