# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for undo/history/oldrevision

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
alias_b = generate_random_string(5)
alias_c = generate_random_string(5)
sitelinks = [["en", "London"], ["de", "London"]]
sitelink_changed = "London Olympics"
changed = "_changed"

describe "Check undo/history/oldrevision" do
  before :all do
    # set up: create item with aliases & sitelinks
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
      page.add_aliases([alias_a, alias_b, alias_c])
      page.add_sitelinks([sitelinks[0]])
    end
  end
  context "undo test change item & check history page" do
    it "should check revision count on history page" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.count_revisions.should == 3
      end
    end
    it "should make some changes to item" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.change_label(label + changed)
        page.change_description(description + changed)
        page.editAliases
        page.aliasesInputFirst_element.clear
        page.aliasesInputEmpty= alias_a + changed
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.editSitelinkLink
        page.pageInputFieldExistingSiteLink= sitelink_changed
        ajax_wait
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
      end
    end
    it "should check revision count on history page" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.count_revisions.should == 7
      end
    end
  end

  context "view old revision test" do
    it "should check functionality of viewing an old revision" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.oldRevision5_element.when_present.click
        sleep 1
      end
      on_page(ItemPage) do |page|
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
        page.get_nth_alias(3).text.should == alias_c
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.editAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.editSitelinkLink?.should be_false
      end
    end
  end

  context "undo functionality test" do
    it "should undo label change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo4_element.when_present.click
        page.undoDel_element.when_present.text.should == label + changed
        page.undoIns_element.when_present.text.should == label
        page.undoDelTitle_element.when_present.text.should == "label / en"
        page.undoInsTitle_element.when_present.text.should == "label / en"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
      end
    end
    it "should try to do the same undo again" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo5_element.when_present.click
        page.undoDel?.should be_false
        page.undoIns?.should be_false
      end
    end
    it "should undo description change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo4_element.when_present.click
        page.undoDel_element.when_present.text.should == description + changed
        page.undoIns_element.when_present.text.should == description
        page.undoDelTitle_element.when_present.text.should == "description / en"
        page.undoInsTitle_element.when_present.text.should == "description / en"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description
      end
    end
    it "should undo the undo of the description change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo1_element.when_present.click
        page.undoDel_element.when_present.text.should == description
        page.undoIns_element.when_present.text.should == description + changed
        page.undoDelTitle_element.when_present.text.should == "description / en"
        page.undoInsTitle_element.when_present.text.should == "description / en"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description + changed
      end
    end
    it "should add a second siteling and undo the change" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_sitelinks([sitelinks[1]])
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo1_element.when_present.click
        page.undoDel_element.when_present.text.should == sitelinks[1][1]
        page.undoIns?.should be_false
        page.undoDelTitle_element.when_present.text.should == "links / dewiki / name"
        page.undoInsTitle_element.when_present.text.should == "links / dewiki / name"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.get_number_of_sitelinks_from_counter.should == 1
      end
    end
    it "should undo the change on the first language link" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo6_element.when_present.click
        page.undoDel_element.when_present.text.should == sitelink_changed
        page.undoIns_element.when_present.text.should == sitelinks[0][1]
        page.undoDelTitle_element.when_present.text.should == "links / enwiki / name"
        page.undoInsTitle_element.when_present.text.should == "links / enwiki / name"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.get_number_of_sitelinks_from_counter.should == 1
        page.englishSitelink_element.text.should == sitelinks[0][1]
      end
    end
    it "should undo the aliases change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo8_element.when_present.click
        page.undoDel_element.when_present.text.should == alias_a + changed
        page.undoIns_element.when_present.text.should == alias_a
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.get_nth_alias(3).text.should == alias_a
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
