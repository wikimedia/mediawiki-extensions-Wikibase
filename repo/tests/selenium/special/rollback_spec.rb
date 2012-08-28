# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for rollback/revert

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
sitelinks = [["en", "Vancouver"], ["de", "Vancouver"]]
sitelink_changed = "Vancouver Olympics"
changed = "_changed"

describe "Check revert/rollback" do

  context "rollback test setup" do
    it "should create item, enter label, description and aliases" do
      visit_page(LoginPage) do |page|
        page.logout_user
      end
      visit_page(ItemPage) do |page|
        page.create_new_item(label, description)
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.aliasesInputEmpty= alias_a
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.add_sitelink(sitelinks[0][0], sitelinks[0][1])
        page.add_sitelink(sitelinks[1][0], sitelinks[1][1])
      end
    end
    it "should make some changes to item" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.editLabelLink
        page.labelInputField= label + changed
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.editDescriptionLink
        page.descriptionInputField= description + changed
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.editAliases
        page.aliasesInputFirst_element.clear
        page.aliasesInputEmpty= alias_a + changed
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.editSitelinkLink
        page.pageInputField= sitelink_changed
        ajax_wait
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end

  context "rollback functionality test" do
    it "should login as admin and rollback changes by last user" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.rollbackLink_element.when_present.click
        page.returnToItemLink_element.when_present.click
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.itemLabelSpan.should == label
        page.itemDescriptionSpan.should == description
        page.get_nth_alias(1).text.should == alias_a
        page.englishSitelink_element.text.should == sitelinks[0][1]
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end

  context "rollback test teardown" do
    it "should remove all sitelinks" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_sitelinks_to_load
        page.remove_all_sitelinks
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end
end
