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
  before :all do
    # set up: create item, enter label, description and aliases & make some changes to item
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
      page.addAliases
      page.aliasesInputEmpty= alias_a
      page.saveAliases
      ajax_wait
      page.wait_for_api_callback
      @browser.refresh
      page.wait_for_entity_to_load
      page.change_label(label + changed)
      page.change_description(description + changed)
      page.editAliases
      page.aliasesInputFirst_element.clear
      page.aliasesInputEmpty= alias_a + changed
      page.saveAliases
      ajax_wait
      page.wait_for_api_callback
    end
  end

  context "restore functionality test" do
    it "should restore old revision" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.curLink4_element.when_present.click
        page.restoreLink_element.when_present.click
        page.undoSave
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
        page.get_nth_alias(1).text.should == alias_a
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
  end

end
