# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for restore

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
changed = "_changed"

describe "Check restore" do

  context "restore test setup" do
    it "should create item, enter label, description and aliases" do
      visit_page(ItemPage) do |page|
        page.create_new_item(label, description)
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.aliasesInputEmpty= alias_a
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
      end
    end
    it "should make some changes to item" do
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
      end
    end
  end

  context "restore functionality test" do
    it "should restore old revision" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.curLink4_element.when_present.click
        page.restoreLink_element.when_present.click
        page.undoSave
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.itemLabelSpan.should == label
        page.itemDescriptionSpan.should == description
        page.getNthAlias(1).text.should == alias_a
      end
    end
  end

  context "restore test teardown" do
    it "should remove all sitelinks" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_sitelinks_to_load
        page.remove_all_sitelinks
      end
    end
  end

end
